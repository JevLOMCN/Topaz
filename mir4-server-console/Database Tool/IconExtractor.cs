using System;
using System.Drawing;
using System.IO;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using SkiaSharp;
using Newtonsoft.Json;
using CUE4Parse.FileProvider;
using CUE4Parse.UE4.Versions;
using CUE4Parse.Encryption.Aes;
using CUE4Parse.UE4.Objects.Core.Misc;
using CUE4Parse.Compression;
using CUE4Parse.UE4.Assets.Exports.Texture;
using CUE4Parse_Conversion.Textures;
using CUE4Parse_Conversion.Sounds;
using CUE4Parse.UE4.Assets.Exports;

namespace Server_Console.Database_Tool
{
    public static class IconExtractor
    {
        private static readonly string iconDirectory = FileManager.GetFilePath("Assets/Icons/");
        private static LogDelegate? LogMethod;
        public static DefaultFileProvider? Provider { get; private set; }
        public static void InitializeProvider()
        {
            if (Provider == null)
            {
                Provider = new DefaultFileProvider(FileManager.GetFilePath("Assets/Paks"), SearchOption.AllDirectories, true, new VersionContainer(EGame.GAME_UE4_24));
                Provider.Initialize();
                Provider.SubmitKey(new FGuid(), new FAesKey(Config.AESKey));
            }
        }
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

                if (!File.Exists(iconPath))
                    return null;

                icon = GetImageFromFile(iconPath);
            }

            if (icon is Bitmap bitmapIcon)
            {
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
            return null;
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
                iconPictureBox.Image = Image.FromFile(Path.Combine(iconDirectory, "unknown.png"));
                Log($"Error setting icon in PictureBox: {ex.Message}");
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

        private static Bitmap? GetIconFromUE4(string iconId)
        {
            if (FileManager.IconPathMap.TryGetValue(iconId, out var iconPath) is false)
                return null;

            var allExports = Provider.LoadAllObjects(iconPath);

            foreach (var export in allExports)
            {
                if (export is UObject obj)
                {
                    var properties = (export as UObject).Properties;
                    float? bakedSourceUV_X = null, bakedSourceUV_Y = null;
                    float? bakedSourceDimension_X = null, bakedSourceDimension_Y = null;
                    string bakedSourceTextureObjectPath = null, bakedSourceTextureObjectName = null;

                    foreach (var property in properties)
                    {
                        switch (property.Name.Text)
                        {
                            case "BakedSourceUV":
                                var BakedSourceUV = property.Tag.ToString() ?? string.Empty;
                                (bakedSourceUV_X, bakedSourceUV_Y) = ParseVector2D(BakedSourceUV);
                                break;

                            case "BakedSourceDimension":
                                var BakedSourceDimension = property.Tag.ToString() ?? string.Empty;
                                (bakedSourceDimension_X, bakedSourceDimension_Y) = ParseVector2D(BakedSourceDimension);
                                break;

                            case "BakedSourceTexture":
                                var BakedSourceTexture = property.Tag.ToString() ?? string.Empty;
                                (bakedSourceTextureObjectPath, bakedSourceTextureObjectName) = ParseBakedSourceTexture(BakedSourceTexture);
                                break;

                            default:
                                break;
                        }
                    }

                    if (bakedSourceTextureObjectPath != null)
                    {
                        string texturePath = ReformatTexturePath(bakedSourceTextureObjectPath);
                        var texture2D = Provider.LoadObject<UTexture2D>(texturePath);
                        if (texture2D != null)
                        {
                            var skBitmap = texture2D.Decode(ETexturePlatform.DesktopMobile);
                            Bitmap bitmap = ConvertSKBitmapToBitmap(skBitmap);
                            return CropBitmap(bitmap, bakedSourceUV_X, bakedSourceUV_Y, bakedSourceDimension_X, bakedSourceDimension_Y);
                        }
                    }

                }
            }

            Log("No valid object found.");
            return null;
        }

        private static Bitmap? CropBitmap(Bitmap image, float? bakedSourceUVX, float? bakedSourceUVY, float? bakedSourceDimensionX, float? bakedSourceDimensionY)
        {
            if (bakedSourceUVX.HasValue && bakedSourceUVY.HasValue && bakedSourceDimensionX.HasValue && bakedSourceDimensionY.HasValue)
            {
                try
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

        private static (float X, float Y) ParseVector2D(string vectorString)
        {
            var regex = new Regex(@"X=(?<X>[-+]?\d*\.\d+|\d+) Y=(?<Y>[-+]?\d*\.\d+|\d+)");
            var match = regex.Match(vectorString);
            if (match.Success)
            {
                float x = float.Parse(match.Groups["X"].Value);
                float y = float.Parse(match.Groups["Y"].Value);
                return (x, y);
            }
            return (0, 0);
        }

        private static (string ObjectPath, string ObjectName) ParseBakedSourceTexture(string textureString)
        {
            var parts = textureString.Split(new[] { '\'' }, StringSplitOptions.RemoveEmptyEntries);
            if (parts.Length >= 2)
            {
                string objectName = parts[0];
                string objectPath = parts[1];
                return (objectPath, objectName);
            }
            return (string.Empty, string.Empty);
        }

        public static string ReformatTexturePath(string input)
        {
            string[] parts = input.Split('.');

            if (parts.Length < 2)
                return "";

            string lastPart = parts[1];
            string[] subParts = lastPart.Split(':');

            if (subParts.Length < 2)
                return "";

            string result = $"{parts[0]}.{subParts[1]}";
            return result;
        }

        public static void InitZlibSync()
        {
            var zlibPath = FileManager.GetFilePath("./Lib/" + ZlibHelper.DLL_NAME);
            if (!File.Exists(zlibPath))
            {
                ZlibHelper.DownloadDllAsync(zlibPath);
            }

            ZlibHelper.Initialize(zlibPath);
        }

        private static Bitmap ConvertSKBitmapToBitmap(SKBitmap skBitmap)
        {
            using (var ms = new MemoryStream())
            {
                using (var image = SKImage.FromBitmap(skBitmap))
                using (var data = image.Encode(SKEncodedImageFormat.Png, 100))
                {
                    data.SaveTo(ms);
                }

                ms.Seek(0, SeekOrigin.Begin);
                return new Bitmap(ms);
            }
        }
    }

    public delegate void LogDelegate(string message);
}
