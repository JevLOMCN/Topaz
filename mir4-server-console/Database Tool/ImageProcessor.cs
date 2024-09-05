using System;
using System.Drawing;
using System.IO;
using System.Text.RegularExpressions;
using System.Windows.Forms;
using System.Drawing.Drawing2D;
using SkiaSharp;
using Newtonsoft.Json;
using CUE4Parse.UE4.Versions;
using CUE4Parse.UE4.Objects.Core.Misc;
using CUE4Parse.UE4.Assets.Exports;
using CUE4Parse.UE4.Assets.Exports.Texture;
using CUE4Parse.FileProvider;
using CUE4Parse.Encryption.Aes;
using CUE4Parse.Compression;
using CUE4Parse_Conversion.Textures;
using CUE4Parse_Conversion.Sounds;
using Org.BouncyCastle.Asn1.Pkcs;
using System.Collections.Generic;
using System.Drawing.Imaging;
using System.Linq;
using CUE4Parse.Utils;

namespace Server_Console.Database_Tool
{
    public static class ImageProcessor
    {
        private static readonly string iconDirectory = FileManager.GetFilePath("Assets/Icons/");
        public static DefaultFileProvider? Provider { get; private set; }

        private static void Log(string message) => DatabaseTool.Log(message);
        public static void Initialize()
        {
            InitializeZlibSync();
            InitializeProvider();
        }

        private static void InitializeProvider()
        {
            if (Provider != null)
                return;

            Provider = new DefaultFileProvider(FileManager.GetFilePath("Assets/Paks"), SearchOption.AllDirectories, true, new VersionContainer(EGame.GAME_UE4_24));
            Provider.Initialize();
            Provider.SubmitKey(new FGuid(), new FAesKey(Config.AESKey));
        }

        private static void InitializeZlibSync()
        {
            var zlibPath = FileManager.GetFilePath(ZlibHelper.DLL_NAME);
            if (!File.Exists(zlibPath))
            {
                ZlibHelper.DownloadDllAsync(zlibPath);
            }

            ZlibHelper.Initialize(zlibPath);
        }

        public static void DrawAndRecordHeaderText(Graphics graphics, int mapId, int areaId = 0, int areaStringId = 0, int miniGroupId = 0, int miniStringId = 0)
        {
            if (MapPage.mapClickAreas.ContainsKey(mapId))
                return;

            string worldMapText = FileManager.GetStringMessageById(Config.worldMapMessageId);
            string? areaText = areaStringId != 0 ? FileManager.GetStringTemplateById(areaStringId) : null;
            string? miniText = miniStringId != 0 ? FileManager.GetStringTemplateById(miniStringId) : null;

            Font defaultFont = new Font("Arial", 14, FontStyle.Bold);
            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                defaultFont = new Font("Microsoft YaHei", 14, FontStyle.Bold);

            SizeF worldMapTextSize = graphics.MeasureString(worldMapText, defaultFont);

            float paddingTop = 25;
            float paddingLeft = 25;

            PointF textStartPoint = new PointF(paddingLeft, paddingTop);
            graphics.DrawString(worldMapText, defaultFont, Brushes.White, textStartPoint);

            float iconX = textStartPoint.X + worldMapTextSize.Width;
            float iconY = textStartPoint.Y + (worldMapTextSize.Height) / 2 - 20;

            Rectangle worldMapArea = new Rectangle((int)textStartPoint.X, (int)textStartPoint.Y, (int)worldMapTextSize.Width, (int)worldMapTextSize.Height);
            MapPage.mapClickAreas[mapId] = new List<(RectangleF, int)> { (worldMapArea, Config.worldMapDefaultId) };

            if (!string.IsNullOrEmpty(areaText))
            {
                Bitmap? iconBitmap = ImageProcessor.GetIconFromUE4(Config.nextIconPath);
                if (iconBitmap != null)
                {
                    float iconWidth = iconBitmap.Width / 2f;
                    float iconHeight = iconBitmap.Height / 2f;

                    graphics.DrawImage(iconBitmap, iconX, iconY, iconWidth, iconHeight);

                    iconX += iconWidth;

                    PointF areaTextStartPoint = new PointF(iconX, textStartPoint.Y);
                    graphics.DrawString(areaText, defaultFont, Brushes.White, areaTextStartPoint);

                    Rectangle areaMapArea = new Rectangle((int)iconX, (int)textStartPoint.Y, (int)graphics.MeasureString(areaText, defaultFont).Width, (int)worldMapTextSize.Height);
                    MapPage.mapClickAreas[mapId].Add((areaMapArea, areaId));

                    iconX += graphics.MeasureString(areaText, defaultFont).Width;
                }
            }

            if (!string.IsNullOrEmpty(miniText))
            {
                Bitmap? iconBitmapMini = GetIconFromUE4(Config.nextIconPath);
                if (iconBitmapMini != null)
                {
                    float iconWidth = iconBitmapMini.Width / 2f;
                    float iconHeight = iconBitmapMini.Height / 2f;

                    graphics.DrawImage(iconBitmapMini, iconX, iconY, iconWidth, iconHeight);

                    iconX += iconWidth;

                    PointF miniTextStartPoint = new PointF(iconX, textStartPoint.Y);
                    graphics.DrawString(miniText, defaultFont, Brushes.White, miniTextStartPoint);

                    Rectangle miniMapArea = new Rectangle((int)iconX, (int)textStartPoint.Y, (int)graphics.MeasureString(miniText, defaultFont).Width, (int)worldMapTextSize.Height);
                    MapPage.mapClickAreas[mapId].Add((miniMapArea, miniGroupId));
                }
            }
        }
        public static void DrawStageTabs(Graphics graphics, int miniStageId, int miniGroupId, int elliteCheck, int tabX, int tabY, int scaledTabWidth, int scaledTabHeight, Bitmap tabBackground, int backgroundWidth)
        {
            if (!FileManager.MapMiniList.TryGetValue(miniGroupId, out var miniList))
                return;

            Color lineColor = Color.FromArgb(255, 96, 96, 96);
            Color gridColor = Color.FromArgb(128, 13, 18, 32);

            Font defaultFont = new Font("Arial", 12);
            Font smallerFont = new Font("Arial", 10);

            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
            {
                defaultFont = new Font("Microsoft YaHei", elliteCheck == 1 ? 12 : 14, FontStyle.Bold);
                smallerFont = new Font("Microsoft YaHei", 12, FontStyle.Bold);
            }

            int maxWidth = scaledTabWidth - 10;
            int currentTabX = tabX;

            using (Brush brush = new SolidBrush(gridColor))
                graphics.FillRectangle(brush, tabX, tabY, backgroundWidth, scaledTabHeight);

            foreach (var mapMini in miniList)
            {
                if (mapMini.ElliteCheck != elliteCheck)
                    continue;

                int stageId = mapMini.MiniStageId;
                if (!FileManager.MapStageList.TryGetValue(stageId, out var stageData))
                    continue;

                int stringId = stageData.StageNameSID;
                if (!FileManager.StringTemplateMap.TryGetValue(stringId, out var stageName))
                    continue;

                using (Pen linePen = new Pen(lineColor, 2))
                {
                    int verticalX = currentTabX + scaledTabWidth;
                    int startY = tabY + (int)(scaledTabHeight * 0.25);
                    int endY = tabY + (int)(scaledTabHeight * 0.75);
                    graphics.DrawLine(linePen, verticalX, startY, verticalX, endY);
                }

                if (stageId == miniStageId)
                    graphics.DrawImage(tabBackground, currentTabX, tabY, scaledTabWidth, scaledTabHeight);

                Color fontColor = (stageId == miniStageId) ? Color.White : Color.FromArgb(255, 126, 126, 126);
                if (mapMini.ElliteCheck == 1 && stageId == miniStageId)
                    fontColor = Color.FromArgb(255, 255, 155, 0);

                string text = stageName.Text ?? string.Empty;
                SizeF textSize = graphics.MeasureString(text, defaultFont);

                if (textSize.Width > maxWidth)
                {
                    string[] words = text.Split(' ');
                    List<string> lines = new List<string>();
                    string currentLine = "";

                    foreach (string word in words)
                    {
                        string testLine = string.IsNullOrEmpty(currentLine) ? word : currentLine + " " + word;
                        if (graphics.MeasureString(testLine, defaultFont).Width > maxWidth)
                        {
                            lines.Add(currentLine);
                            currentLine = word;
                        }
                        else
                        {
                            currentLine = testLine;
                        }
                    }

                    if (!string.IsNullOrEmpty(currentLine))
                        lines.Add(currentLine);

                    float textY = tabY + (scaledTabHeight - (lines.Count * smallerFont.Height)) / 2;
                    for (int i = 0; i < lines.Count; i++)
                    {
                        using (Brush brush = new SolidBrush(fontColor))
                        {
                            SizeF lineSize = graphics.MeasureString(lines[i], smallerFont);
                            float textX = currentTabX + (scaledTabWidth - lineSize.Width) / 2;
                            graphics.DrawString(lines[i], smallerFont, brush, textX, textY);
                        }
                        textY += smallerFont.Height;
                    }
                }
                else
                {
                    float textX = currentTabX + (scaledTabWidth - textSize.Width) / 2;
                    float textY = tabY + (scaledTabHeight - textSize.Height) / 2;

                    using (Brush brush = new SolidBrush(fontColor))
                        graphics.DrawString(text, defaultFont, brush, textX, textY);
                }

                RectangleF touchArea = new RectangleF(currentTabX, tabY, scaledTabWidth, scaledTabHeight);

                MapPage.mapClickAreas[miniStageId].Add((touchArea, stageId));

                currentTabX += scaledTabWidth;
            }
        }

        public static void DrawRightPanel(Graphics graphics, int miniStageId, int elliteStageId, int elliteCheck, Bitmap background, int tabY, int scaledTabHeight)
        {
            Font defaultFont = new Font("Arial", 12);
            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                defaultFont = new Font("Microsoft YaHei", 12);

            Color gridColor = Color.FromArgb(128, 13, 18, 32);

            using (Brush brush = new SolidBrush(gridColor))
            {
                int xStart = background.Width * 70 / 100;
                int width = background.Width * 30 / 100;
                int yStart = tabY + scaledTabHeight + 2;
                int height = background.Height - yStart;

                graphics.FillRectangle(brush, xStart, yStart, width, height);

                if (elliteStageId == 0)
                    return;

                Bitmap? btnSwitch = GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Btn_nor_02_Sprite");

                if (btnSwitch is null)
                    return;

                if (!FileManager.StringTemplateMap.TryGetValue(920913, out var btnText))
                    return;

                SizeF textSize = graphics.MeasureString(btnText.Text, defaultFont);

                int btnWidth = 150;
                int btnHeight = 50;

                int btnX = xStart + width - btnWidth - 30;
                int btnY = 20;// yStart + 15;

                graphics.DrawImage(btnSwitch, btnX, btnY, btnWidth, btnHeight);

                float buttonTextX = btnX + (btnWidth - textSize.Width) / 2;
                float buttonTextY = btnY + (btnHeight - textSize.Height) / 2;

                using (Brush textBrush = new SolidBrush(Color.White))
                {
                    graphics.DrawString(btnText.Text, defaultFont, textBrush, buttonTextX + 2, buttonTextY + 2);
                }

                string textCommon = elliteCheck == 1
                    ? $"{FileManager.StringTemplateMap[1010118].Text} {FileManager.StringTemplateMap[920914].Text}"
                    : $"{FileManager.StringTemplateMap[2710003].Text} {FileManager.StringTemplateMap[920914].Text}";
                /*
                if (!string.IsNullOrEmpty(textCommon))
                {
                    string[] wrappedText = WrapText(textCommon, defaultFont, 100, graphics);
                    float textX = btnX - 20;
                    float textY = btnY + (btnHeight - wrappedText.Length * graphics.MeasureString("A", defaultFont).Height) / 2;

                    Color textColor = elliteCheck == 1 ? Color.FromArgb(255, 255, 155, 0) : Color.FromArgb(255, 69, 135, 186);
                    using (Brush textBrush = new SolidBrush(textColor))
                    {
                        float lineHeight = graphics.MeasureString("A", defaultFont).Height;
                        for (int i = 0; i < wrappedText.Length; i++)
                        {
                            float lineTextWidth = graphics.MeasureString(wrappedText[i], defaultFont).Width;
                            float alignedTextX = textX - lineTextWidth;
                            graphics.DrawString(wrappedText[i], defaultFont, textBrush, alignedTextX, textY + i * lineHeight);
                        }
                    }
                }
                */

                if (!string.IsNullOrEmpty(textCommon))
                {
                    SizeF textCommonSize = graphics.MeasureString(textCommon, defaultFont);
                    float textX = btnX - 20 - textCommonSize.Width;
                    float textY = btnY + (btnHeight - textCommonSize.Height) / 2;
                    Color textColor = elliteCheck == 1 ? Color.FromArgb(255, 255, 155, 0) : Color.FromArgb(255, 69, 135, 186);

                    using (Brush textBrush = new SolidBrush(textColor))
                        graphics.DrawString(textCommon, defaultFont, textBrush, textX, textY);
                }

                RectangleF touchArea = new RectangleF(btnX, btnY, btnWidth, btnHeight);
                MapPage.mapClickAreas[miniStageId].Add((touchArea, elliteStageId));
            }
        }

        public static void DrawCenteredText(Graphics graphics, List<(string text, Font font, Color color, bool needBg)> textSegments, PointF centerPoint, float maxWidth, float iconTextSpacing, float lineSpacing)
        {
            graphics.TextRenderingHint = System.Drawing.Text.TextRenderingHint.AntiAlias;

            var lines = new List<List<(string text, Font font, Color color, bool needBg)>>();
            var currentLine = new List<(string text, Font font, Color color, bool needBg)>();

            foreach (var segment in textSegments)
            {
                var words = segment.text.Split(' ');

                foreach (var word in words)
                {
                    string testLine = string.Join(" ", currentLine.Select(x => x.text)) + (currentLine.Count > 0 ? " " : "") + word;
                    if (word.Contains("[") && word.Contains("]"))
                    {
                        if (currentLine.Count > 0)
                        {
                            lines.Add(new List<(string text, Font font, Color color, bool needBg)>(currentLine));
                            currentLine.Clear();
                        }
                        currentLine.Add((word, segment.font, segment.color, segment.needBg));
                        lines.Add(new List<(string text, Font font, Color color, bool needBg)>(currentLine));
                        currentLine.Clear();
                    }
                    else
                    {
                        SizeF size = graphics.MeasureString(testLine, segment.font);
                        if (size.Width > maxWidth)
                        {
                            if (currentLine.Count > 0)
                            {
                                lines.Add(new List<(string text, Font font, Color color, bool needBg)>(currentLine));
                                currentLine.Clear();
                            }
                            currentLine.Add((word, segment.font, segment.color, segment.needBg));
                        }
                        else
                        {
                            currentLine.Add(((currentLine.Count > 0 ? " " : "") + word, segment.font, segment.color, segment.needBg));
                        }
                    }
                }

                if (currentLine.Count > 0)
                {
                    lines.Add(new List<(string text, Font font, Color color, bool needBg)>(currentLine));
                    currentLine.Clear();
                }
            }

            if (lines.Count == 0 && currentLine.Count > 0)
            {
                lines.Add(currentLine);
            }

            float transparency = 0.70f;
            ImageAttributes imageAttributes = new ImageAttributes();
            ColorMatrix colorMatrix = new ColorMatrix
            {
                Matrix33 = transparency
            };

            imageAttributes.SetColorMatrix(colorMatrix, ColorMatrixFlag.Default, ColorAdjustType.Bitmap);

            int bgAddWidth = 30;
            int bgAddHeight = 4;

            float totalTextHeight = lines.Sum(line => graphics.MeasureString(string.Join("", line.Select(x => x.text)), line[0].font).Height)
                                    + (lines.Count - 1) * lineSpacing
                                    + iconTextSpacing;

            PointF startPoint = new PointF(centerPoint.X, centerPoint.Y - totalTextHeight);

            Bitmap? bgText = GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/Frame_txt_Sprite");

            if (bgText != null)
            {
                float maxLineWidth = lines.Max(line => graphics.MeasureString(string.Join("", line.Select(x => x.text)), line[0].font).Width);
                float bgHeight = lines.Where(line => line[0].needBg).Sum(line => graphics.MeasureString(string.Join("", line.Select(x => x.text)), line[0].font).Height)
                                 + (lines.Count(line => line[0].needBg) - 1) * lineSpacing;

                Rectangle bgRect = new Rectangle(
                    (int)(centerPoint.X - maxLineWidth / 2 - bgAddWidth),
                    (int)(startPoint.Y - bgAddHeight),
                    (int)(maxLineWidth + bgAddWidth * 2),
                    (int)(bgHeight + bgAddHeight * 2)
                );

                if (bgHeight > 0)
                    graphics.DrawImage(bgText, bgRect, 0, 0, bgText.Width, bgText.Height, GraphicsUnit.Pixel, imageAttributes);
            }

            foreach (var line in lines)
            {
                if (line.Count == 0 || line.All(l => string.IsNullOrEmpty(l.text))) continue;

                string combinedLine = string.Join("", line.Select(x => x.text));
                Font lineFont = line[0].font;
                Color lineColor = line[0].color;

                SizeF lineSize = graphics.MeasureString(combinedLine, lineFont);
                PointF linePoint = new PointF(centerPoint.X - lineSize.Width / 2, startPoint.Y);

                graphics.DrawString(combinedLine, lineFont, new SolidBrush(lineColor), linePoint);

                startPoint.Y += lineSize.Height + lineSpacing;
            }
        }

        public static void DrawItemIcons(Graphics graphics, ItemViewerData itemViewerData, int bgStartX, int bgStartY, int bgWidth, int bgHeight, Font smallerFont)
        {
            int itemIconSize = 75;
            int itemsPerRow = 5;
            int totalIconWidth = itemIconSize * itemsPerRow;
            int padding = (bgWidth - totalIconWidth) / (itemsPerRow + 1);
            int itemSpacing = padding;
            int itemSpacingBottom = 55;

            int currentX = bgStartX + padding;
            int currentY = bgStartY + padding;

            if (itemViewerData.Money is List<int> moneyList)
            {
                foreach (var moneyId in moneyList)
                {
                    if (!FileManager.MoneyMap.TryGetValue(moneyId, out var moneyData))
                        continue;

                    DrawItem(graphics, moneyData.Icon, 1, 0, 0, moneyData.NameSid, ref currentX, ref currentY, bgStartX, bgWidth, padding, itemIconSize, itemSpacing, itemSpacingBottom, smallerFont);
                }
            }

            var classItemField = $"ClassItem0{Config.worldMapDefaultClassId}";
            var classItemProperty = itemViewerData.GetType().GetProperty(classItemField)?.GetValue(itemViewerData);

            if (classItemProperty is List<int> itemIds)
            {
                foreach (var itemId in itemIds)
                {
                    if (!FileManager.ItemMap.TryGetValue(itemId, out var itemData))
                        continue;

                    DrawItem(graphics, itemData.Icon, itemData.Grade, itemData.Tier, itemData.TradeType, itemData.NameSid, ref currentX, ref currentY, bgStartX, bgWidth, padding, itemIconSize, itemSpacing, itemSpacingBottom, smallerFont);
                }
            }
        }

        public static void DrawItem(Graphics graphics, int iconId, int grade, int tier, int tradeType, int nameSid, ref int currentX, ref int currentY, int bgStartX, int bgWidth, int padding, int itemIconSize, int itemSpacing, int itemSpacingBottom, Font smallerFont)
        {
            Image? iconItem = GetIcon(iconId, grade, tier, tradeType);

            if (iconItem is null)
                return;

            graphics.DrawImage(iconItem, currentX, currentY, itemIconSize, itemIconSize);
            DrawItemBoxString(graphics, nameSid, currentX, currentY + itemIconSize + 5, itemIconSize, smallerFont);

            currentX += itemIconSize + itemSpacing;
            if (currentX + itemIconSize > bgStartX + bgWidth)
            {
                currentX = bgStartX + padding;
                currentY += itemIconSize + itemSpacingBottom;
            }
        }

        private static void DrawItemBoxString(Graphics graphics, int nameSid, float x, float y, int maxWidth, Font font)
        {
            string? itemName = FileManager.StringTemplateMap.TryGetValue(nameSid, out var nameText) ? nameText.Text : "Unknown";

            SizeF originalSize = graphics.MeasureString(itemName, font);
            int adjustedMaxWidth = maxWidth + 35;

            if (originalSize.Width > adjustedMaxWidth)
            {
                string[] nameLines = WrapText(itemName, font, adjustedMaxWidth, graphics);
                float lineHeight = graphics.MeasureString("A", font).Height;

                for (int i = 0; i < nameLines.Length; i++)
                {
                    SizeF lineSize = graphics.MeasureString(nameLines[i], font);
                    float centeredX = x + (maxWidth - lineSize.Width) / 2;
                    graphics.DrawString(nameLines[i], font, Brushes.White, centeredX, y + i * lineHeight);
                }
            }
            else
            {
                float centeredX = x + (maxWidth - originalSize.Width) / 2;
                graphics.DrawString(itemName, font, Brushes.White, centeredX, y);
            }
        }

        private static string[] WrapText(string text, Font font, int maxWidth, Graphics graphics)
        {
            List<string> lines = new List<string>();
            string[] words = text.Split(' ');
            string currentLine = "";

            foreach (string word in words)
            {
                string testLine = string.IsNullOrEmpty(currentLine) ? word : currentLine + " " + word;
                SizeF size = graphics.MeasureString(testLine, font);

                if (size.Width > maxWidth)
                {
                    if (!string.IsNullOrEmpty(currentLine))
                    {
                        lines.Add(currentLine);
                        currentLine = word;
                    }
                    else
                    {
                        int charIndex = 0;
                        while (charIndex < word.Length)
                        {
                            string subString = word.Substring(0, charIndex + 1);
                            SizeF subStringSize = graphics.MeasureString(subString, font);

                            if (subStringSize.Width > maxWidth)
                            {
                                lines.Add(word.Substring(0, charIndex));
                                currentLine = word.Substring(charIndex);
                                break;
                            }
                            charIndex++;
                        }
                    }
                }
                else
                {
                    currentLine = testLine;
                }
            }

            if (!string.IsNullOrEmpty(currentLine))
                lines.Add(currentLine);

            return lines.ToArray();
        }

        public static Image? GetIcon(int iconId, int grade, int tier, int tradeType)
        {
            Image? icon = GetIconFromUE4(iconId);

            if (icon == null)
                icon = GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Icon_Quest003_Sprite");

            if (icon is Bitmap bitmapIcon)
            {
                Image? background = GetBackgroundImage(grade);
                Image? tierIcon = tier > 1 ? GetTierImage(tier) : null;
                Image? tradeIcon = tradeType == 1 ? GetIconFromUE4(100057) : null;

                if (background != null)
                {
                    return CombineImages(background, tierIcon, icon, tradeIcon);
                }
                else
                {
                    return icon;
                }
            }
            return null;
        }

        private static Image? GetBackgroundImage(int grade) => GetIconFromUE4(200 + grade);

        private static Image? GetTierImage(int tier) => GetIconFromUE4(820 + tier);

        private static Image CombineImages(Image background, Image? tierIcon, Image icon, Image tradeIcon)
        {
            int width = background.Width;
            int height = background.Height;

            Bitmap combined = new Bitmap(width, height);
            using (Graphics g = Graphics.FromImage(combined))
            {
                g.DrawImage(background, 0, 0, width, height);

                g.DrawImage(icon, new Rectangle(
                    (width - icon.Width) / 2,
                    (height - icon.Height) / 2,
                    icon.Width, icon.Height
                ));

                if (tierIcon != null)
                {
                    int tierIconWidth = tierIcon.Width;
                    int tierIconHeight = tierIcon.Height;

                    g.DrawImage(tierIcon, new Rectangle(
                        0,
                        height - tierIconHeight,
                        tierIconWidth,
                        tierIconHeight
                    ));
                    tierIcon?.Dispose();
                }

                if (tradeIcon != null)
                {
                    int tradeIconWidth = (int)(tradeIcon.Width * 0.8);
                    int tradeIconHeight = (int)(tradeIcon.Height * 0.8);

                    g.DrawImage(tradeIcon, new Rectangle(
                        0,
                        0,
                        tradeIconWidth,
                        tradeIconHeight
                    ));

                    tradeIcon.Dispose();
                }
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

        public static void SetIconInPictureBox(int iconId, int grade, int tier, int tradeType, PictureBox iconPictureBox)
        {
            try
            {
                Image? iconBitmap = GetIcon(iconId, grade, tier, tradeType);
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
                g.InterpolationMode = InterpolationMode.HighQualityBicubic;
                g.Clear(Color.Transparent);
                int x = (width - newWidth) / 2;
                int y = (height - newHeight) / 2;
                g.DrawImage(image, x, y, newWidth, newHeight);
            }

            return resized;
        }

        public static Bitmap? GetIconFromUE4(int iconId)
        {
            if (FileManager.IconPathMap.TryGetValue(iconId, out var iconPath) is false)
                return null;

            return LoadAssetObjects(iconPath);
        }

        public static Bitmap? GetIconFromUE4(string iconPath)
        {
            if (iconPath == null)
                throw new Exception($"Failed to load asset from path: {iconPath}");

            return LoadAssetObjects(iconPath);
        }

        private static Bitmap? LoadAssetObjects(string iconPath)
        {
            var allExports = Provider.LoadAllObjects(iconPath);

            foreach (var export in allExports)
            {
                if (export is UObject obj)
                {
                    var properties = (export as UObject).Properties;
                    float? bakedSourceUV_X = null, bakedSourceUV_Y = null;
                    float? bakedSourceDimension_X = null, bakedSourceDimension_Y = null;
                    string? bakedSourceTextureObjectPath = null, bakedSourceTextureObjectName = null;

                    foreach (var property in properties)
                    {
                        switch (property.Name.Text)
                        {
                            case "BakedSourceUV":
                                var BakedSourceUV = property?.Tag?.ToString() ?? string.Empty;
                                (bakedSourceUV_X, bakedSourceUV_Y) = ParseVector2D(BakedSourceUV);
                                break;

                            case "BakedSourceDimension":
                                var BakedSourceDimension = property?.Tag?.ToString() ?? string.Empty;
                                (bakedSourceDimension_X, bakedSourceDimension_Y) = ParseVector2D(BakedSourceDimension);
                                break;

                            case "BakedSourceTexture":
                                var BakedSourceTexture = property?.Tag?.ToString() ?? string.Empty;
                                (bakedSourceTextureObjectPath, bakedSourceTextureObjectName) = ParseBakedSourceTexture(BakedSourceTexture);
                                break;

                            default:
                                break;
                        }
                    }

                    if (bakedSourceTextureObjectPath != null)
                    {
                        string texturePath = ReformatTexturePath(bakedSourceTextureObjectPath);
                        Bitmap? bitmap = LoadTextureObject(texturePath);
                        if (bitmap != null)
                        {
                            return CropBitmap(bitmap, bakedSourceUV_X, bakedSourceUV_Y, bakedSourceDimension_X, bakedSourceDimension_Y);
                        }
                    }

                }
            }

            Log("No valid object found.");
            return null;
        }

        public static Bitmap? LoadTextureObject(string texturePath)
        {
            UTexture2D texture2D = Provider.LoadObject<UTexture2D>(texturePath);
            Bitmap? bitmap = null;
            if (texture2D != null)
            {
                var skBitmap = texture2D.Decode(ETexturePlatform.DesktopMobile);
                if (skBitmap != null)
                    bitmap = ConvertSKBitmapToBitmap(skBitmap);
            }
            return bitmap;
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
}
