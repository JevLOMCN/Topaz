using System;
using System.Drawing;
using System.IO;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using UAssetAPI;
using UAssetAPI.ExportTypes;
using UAssetAPI.PropertyTypes.Structs;
using UAssetAPI.UnrealTypes;

namespace Server_Console.Database_Tool
{
    public static class IconExtractor
    {
        private static readonly string iconDirectory = FileManager.GetFilePath("Assets/Icons/");
        private static LogDelegate? LogMethod;

        public static void SetLogMethod(LogDelegate logMethod)
        {
            LogMethod = logMethod;
        }

        public static void Log(string message)
        {
            LogMethod?.Invoke(message);
        }

        public static Image? GetIcon(string iconId, string grade, Size pictureBoxSize)
        {
            Image? icon = GetIconFromUE4(iconId);

            if (icon == null)
            {
                string iconPath = Path.Combine(iconDirectory, $"{iconId}.png");
                if (!File.Exists(iconPath))
                    iconPath = Path.Combine(iconDirectory, "unknown.png");

                icon = GetImageFromFile(iconPath);
            }

            Image resizedIcon = ResizeImage(icon, 180, 180);
            Image? background = GetBackgroundImage(grade);

            if (background != null)
            {
                if (background.Width != 256 || background.Height != 256)
                {
                    background = ResizeImage(background, 256, 256);
                }
                return CombineImages(background, resizedIcon, pictureBoxSize);
            }
            else
            {
                return ResizeImage(resizedIcon, pictureBoxSize.Width, pictureBoxSize.Height);
            }
        }

        private static Image? GetBackgroundImage(string grade)
        {
            string backgroundPath = Path.Combine(iconDirectory, $"grade-{grade}.png");
            if (!File.Exists(backgroundPath))
                return null;

            return GetImageFromFile(backgroundPath);
        }

        private static Image CombineImages(Image background, Image icon, Size pictureBoxSize)
        {
            int width = Math.Max(background.Width, icon.Width);
            int height = Math.Max(background.Height, icon.Height);

            if (background.Width < width || background.Height < height)
            {
                Bitmap newBackground = new Bitmap(width, height);
                using (Graphics g = Graphics.FromImage(newBackground))
                {
                    g.Clear(Color.Transparent);
                    g.DrawImage(background, 0, 0, background.Width, background.Height);
                }
                background.Dispose();
                background = newBackground;
            }

            Bitmap combined = new Bitmap(pictureBoxSize.Width, pictureBoxSize.Height);
            using (Graphics g = Graphics.FromImage(combined))
            {
                g.DrawImage(background, 0, 0, pictureBoxSize.Width, pictureBoxSize.Height);
                g.DrawImage(icon, new Rectangle((pictureBoxSize.Width - icon.Width) / 2, (pictureBoxSize.Height - icon.Height) / 2, icon.Width, icon.Height));
            }

            background.Dispose();
            icon.Dispose();

            return combined;
        }

        private static Image GetImageFromFile(string path)
        {
            try
            {
                using (FileStream fs = new FileStream(path, FileMode.Open, FileAccess.Read))
                {
                    return Image.FromStream(fs);
                }
            }
            catch (Exception ex)
            {
                Log($"Error loading image from {path}: {ex.Message}");
                throw;
            }
        }

        public static void SetIconInPictureBox(string iconId, string grade, PictureBox iconPictureBox)
        {
            try
            {
                Image? iconBitmap = GetIcon(iconId, grade, iconPictureBox.Size);
                iconPictureBox.Image = iconBitmap;
            }
            catch (Exception ex)
            {
                Log($"Error setting icon in PictureBox: {ex.Message}");
                iconPictureBox.Image = Image.FromFile(Path.Combine(iconDirectory, "unknown.png"));
            }
        }

        private static Image ResizeImage(Image image, int width, int height)
        {
            float scale = Math.Max((float)width / image.Width, (float)height / image.Height);
            int newWidth = (int)(image.Width * scale);
            int newHeight = (int)(image.Height * scale);

            Bitmap resized = new Bitmap(width, height);
            using (Graphics g = Graphics.FromImage(resized))
            {
                g.InterpolationMode = System.Drawing.Drawing2D.InterpolationMode.HighQualityBicubic;
                g.Clear(Color.Transparent);
                int x = (width - newWidth) / 2;
                int y = (height - newHeight) / 2;
                g.DrawImage(image, x, y, newWidth, newHeight);
            }

            return resized;
        }

        public static Bitmap? GetIconFromUE4(string iconId)
        {
            if (FileManager.IconPathMap.TryGetValue(iconId, out var iconPath) && File.Exists(iconPath))
            {
                return GetAssetUV(iconPath);
            }
            return null;
        }

        public static Bitmap? GetAssetUV(string assetPath)
        {
            try
            {
                var textureAsset = new UAsset(assetPath, EngineVersion.VER_UE4_24);

                var nameReference = textureAsset.GetNameReference(0).Value;
                var lastSegment = nameReference.Split('/').Last();
                var picAssetPath = $"./Assets/Atlas_N/{lastSegment}.uasset.png";

                if (textureAsset.Exports.Count < 2)
                {
                    Log("Not enough exports in asset.");
                    return null;
                }

                var textureExport = textureAsset.Exports[1];

                if (textureExport is NormalExport normalExport)
                {
                    float? bakedSourceUVX = null, bakedSourceUVY = null;
                    float? bakedSourceDimensionX = null, bakedSourceDimensionY = null;

                    foreach (var property in normalExport.Data)
                    {
                        if (property is StructPropertyData structProperty)
                        {
                            foreach (var innerProperty in structProperty.Value)
                            {
                                if (property.Name.ToString() == "BakedSourceUV")
                                {
                                    var value = innerProperty.ToString() ?? string.Empty;
                                    (bakedSourceUVX, bakedSourceUVY) = ParseVector2D(value);
                                }
                                else if (property.Name.ToString() == "BakedSourceDimension")
                                {
                                    var value = innerProperty.ToString() ?? string.Empty;
                                    (bakedSourceDimensionX, bakedSourceDimensionY) = ParseVector2D(value);
                                }
                            }
                        }
                    }
                    return GetImageUV(picAssetPath, bakedSourceUVX, bakedSourceUVY, bakedSourceDimensionX, bakedSourceDimensionY);
                }
            }
            catch (Exception ex)
            {
                Log($"Exception in GetAssetUV: {ex.Message}");
            }
            return null;
        }

        private static Bitmap? GetImageUV(string picAssetPath, float? bakedSourceUVX, float? bakedSourceUVY, float? bakedSourceDimensionX, float? bakedSourceDimensionY)
        {
            if (bakedSourceUVX.HasValue && bakedSourceUVY.HasValue && bakedSourceDimensionX.HasValue && bakedSourceDimensionY.HasValue)
            {
                try
                {
                    if (!File.Exists(picAssetPath))
                    {
                        return null;
                    }

                    using (var image = Image.FromFile(picAssetPath))
                    {
                        var x = (int)bakedSourceUVX.Value;
                        var y = (int)bakedSourceUVY.Value;
                        var width = (int)bakedSourceDimensionX.Value;
                        var height = (int)bakedSourceDimensionY.Value;

                        if (x < 0 || y < 0 || width <= 0 || height <= 0 || x + width > image.Width || y + height > image.Height)
                        {
                            return null;
                        }

                        var cutArea = new Rectangle(x, y, width, height);

                        Bitmap bitmap;
                        using (var tempBitmap = new Bitmap(cutArea.Width, cutArea.Height))
                        {
                            using (var graphics = Graphics.FromImage(tempBitmap))
                            {
                                graphics.Clear(Color.Transparent);
                                graphics.DrawImage(image, new Rectangle(0, 0, cutArea.Width, cutArea.Height), cutArea, GraphicsUnit.Pixel);
                            }

                            bitmap = new Bitmap(tempBitmap);
                        }

                        return bitmap;
                    }
                }
                catch (OutOfMemoryException ex)
                {
                    Log($"OutOfMemoryException in GetImageUV: {ex.Message}");
                    return null;
                }
                catch (Exception ex)
                {
                    Log($"Exception in GetImageUV: {ex.Message}");
                    return null;
                }
            }
            else
            {
                Log("Invalid UV or Dimension values.");
                return null;
            }
        }

        private static (float? X, float? Y) ParseVector2D(string value)
        {
            var match = Regex.Match(value, @"\((\d+),\s*(\d+)\)");
            if (match.Success)
            {
                if (float.TryParse(match.Groups[1].Value, out var x) && float.TryParse(match.Groups[2].Value, out var y))
                {
                    return (x, y);
                }
            }
            return (null, null);
        }
    }

    public delegate void LogDelegate(string message);
}
