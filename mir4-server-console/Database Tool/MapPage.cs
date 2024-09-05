using CUE4Parse.GameTypes.PUBG.Assets.Exports;
using CUE4Parse.UE4.Assets.Exports.Texture;
using Google.Protobuf.WellKnownTypes;
using Org.BouncyCastle.Asn1.Pkcs;
using SkiaSharp;
using System;
using System.Collections.Concurrent;
using System.Collections.Generic;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Drawing.Imaging;
using System.Linq;
using System.Reflection;
using System.Runtime.InteropServices;
using System.Threading.Tasks;
using System.Windows.Forms;
using static Mysqlx.Expect.Open.Types.Condition.Types;
using static System.Windows.Forms.VisualStyles.VisualStyleElement.TrayNotify;

namespace Server_Console.Database_Tool
{
    public static class MapPage
    {
        private static void Log(string message) => DatabaseTool.Log(message);
        private static PictureBox? WorldMapBox;
        private static GroupBox? serverInfoGroupBox;
        private static GroupBox? mapGroupBox;
        private static GroupBox? optionsGroupBox;
        private static Panel? leftPanel;
        private static Panel? totalOnlinePanel;
        private static Panel? mapOnlinePanel;
        private static Label? totalOnlineLabel;
        private static Label? mapOnlineLabel;
        private static PictureBox? loadingPictureBox;

        private static int curMapId = 0;
        public static int AllPlayerCount = 0;
        public static Dictionary<int, Bitmap> CachedBitmaps = new Dictionary<int, Bitmap>();
        public static Dictionary<int, Bitmap> CachedMapItems = new Dictionary<int, Bitmap>();
        public static Dictionary<int, List<(RectangleF areaRect, int targetMapId)>> mapClickAreas = new Dictionary<int, List<(RectangleF, int)>>();
        public static readonly Dictionary<int, Bitmap> AvatarBitmaps = new Dictionary<int, Bitmap>();

        public static void Initialize(TabControl tabControl)
        {
            var tabPage = new TabPage
            {
                Name = "MapsPage",
                Padding = new Padding(3),
                Size = new Size(1335, 501),
                TabIndex = 0,
                Text = "Maps",
                UseVisualStyleBackColor = true,
                Location = new Point(4, 34)
            };

            InitializeComponent(tabPage);
            tabControl.Controls.Add(tabPage);
        }

        private static void InitializeComponent(TabPage tabPage)
        {
            InitializeLeftPanel();
            InitializeMapGroupBox();

            tabPage.Controls.Add(mapGroupBox);
            tabPage.Controls.Add(leftPanel);
        }

        private static void InitializeLeftPanel()
        {
            leftPanel = new Panel
            {
                Dock = DockStyle.Left,
                Width = 300,
                BackColor = Color.White
            };

            InitializeServerInfoGroupBox();
            InitializeOptionsGroupBox();

            serverInfoGroupBox.Dock = DockStyle.Top;
            optionsGroupBox.Dock = DockStyle.Fill;

            leftPanel.Controls.Add(optionsGroupBox);
            leftPanel.Controls.Add(serverInfoGroupBox);
        }

        private static void InitializeServerInfoGroupBox()
        {
            serverInfoGroupBox = new GroupBox
            {
                Text = "Server Info",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Dock = DockStyle.Top,
                Height = 220,
                BackColor = Color.White,
                Padding = new Padding(10)
            };

            InitializeTotalOnlinePanel();
            InitializeMapOnlinePanel();

            serverInfoGroupBox.Controls.Add(totalOnlinePanel);
            serverInfoGroupBox.Controls.Add(mapOnlinePanel);
        }

        private static void InitializeOptionsGroupBox()
        {
            optionsGroupBox = new GroupBox
            {
                Text = "Options",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Width = 300,
                BackColor = Color.White,
                Padding = new Padding(10),
                Dock = DockStyle.Top, 
                Height = 250 
            };

            InitializeOptionsControls();
        }

        private static void InitializeOptionsControls()
        {
            int startY = 30;

            Label professionLabel = new Label
            {
                Text = "Select Class:",
                Font = new Font("Segoe UI", 9F, FontStyle.Bold),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(30, startY),
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            ComboBox professionComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(30, startY + 25),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            professionComboBox.Items.AddRange(new[] { "Warrior", "Sorcerer", "Taoist", "Lancer", "Arbalist" }); //"Darkist"
            professionComboBox.SelectedIndex = Math.Max(0, Config.worldMapDefaultClassId - 1);

            CheckBox showPlayerCheckBox = new CheckBox
            {
                Text = "Show Player",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(30, startY + 65),
                Checked = true,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            CheckBox showBossCheckBox = new CheckBox
            {
                Text = "Show Boss",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(30, startY + 90),
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            CheckBox showMiningZoneCheckBox = new CheckBox
            {
                Text = "Show Mining Zone",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(30, startY + 115),
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            CheckBox showGatheringZoneCheckBox = new CheckBox
            {
                Text = "Show Gathering Zone",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(30, startY + 140),
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            ComboBox mapSearchComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(30, startY + 170),
                Width = 120,
                DropDownStyle = ComboBoxStyle.DropDown,
                Anchor = AnchorStyles.Top | AnchorStyles.Left,
                AutoCompleteMode = AutoCompleteMode.SuggestAppend,
                AutoCompleteSource = AutoCompleteSource.ListItems
            };

            mapSearchComboBox.Items.AddRange(new[] { "Map 1", "Map 2", "Map 3" });
            mapSearchComboBox.SelectedIndex = 0;

            Button switchMapButton = new Button
            {
                Text = "Switch",
                Font = new Font("Segoe UI", 9F),
                Location = new Point(160, startY + 170),
                Width = 70,
                Height = 20,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            // Creating a Player Search Box
            ComboBox playerSearchComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(30, startY + 200),
                Width = 120,
                DropDownStyle = ComboBoxStyle.DropDown,
                Anchor = AnchorStyles.Top | AnchorStyles.Left,
                AutoCompleteMode = AutoCompleteMode.SuggestAppend,
                AutoCompleteSource = AutoCompleteSource.ListItems
            };

            playerSearchComboBox.Items.AddRange(new[] { "Player 1", "Player 2", "Player 3" });
            playerSearchComboBox.SelectedIndex = 0;

            Button searchPlayerButton = new Button
            {
                Text = "Search",
                Font = new Font("Segoe UI", 9F),
                Location = new Point(160, startY + 200),
                Width = 70,
                Height = 20,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            optionsGroupBox.Controls.Add(professionLabel);
            optionsGroupBox.Controls.Add(professionComboBox);
            optionsGroupBox.Controls.Add(showPlayerCheckBox);
            optionsGroupBox.Controls.Add(showBossCheckBox);
            optionsGroupBox.Controls.Add(showMiningZoneCheckBox);
            optionsGroupBox.Controls.Add(showGatheringZoneCheckBox);
            optionsGroupBox.Controls.Add(mapSearchComboBox);
            optionsGroupBox.Controls.Add(switchMapButton);
            optionsGroupBox.Controls.Add(playerSearchComboBox);
            optionsGroupBox.Controls.Add(searchPlayerButton);
        }

        private static void InitializeFormTitle(TabPage tabPage)
        {
            string version = Assembly.GetExecutingAssembly()
                                  .GetCustomAttribute<AssemblyInformationalVersionAttribute>()?
                                  .InformationalVersion ?? "0.0.0";
            string author = Assembly.GetExecutingAssembly()
                                  .GetCustomAttributes<AssemblyMetadataAttribute>()
                                  .FirstOrDefault(a => a.Key == "Authors")?
                                  .Value ?? "Jev + Sumiao";
            tabPage.Text = $"Mir4Tool v{version} [By {author}]";
        }

        private static void InitializeTotalOnlinePanel()
        {
            totalOnlinePanel = new Panel
            {
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Size = new Size(280, 80),
                Location = new Point(10, 30)
            };

            totalOnlineLabel = new Label
            {
                Text = "Total Players: 0",
                Font = new Font("Segoe UI", 12F, FontStyle.Regular, GraphicsUnit.Point),
                ForeColor = Color.Black,
                AutoSize = false,
                TextAlign = ContentAlignment.MiddleCenter,
                Dock = DockStyle.Fill
            };

            totalOnlinePanel.Controls.Add(totalOnlineLabel);
        }

        private static void InitializeMapOnlinePanel()
        {
            mapOnlinePanel = new Panel
            {
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Size = new Size(280, 80),
                Location = new Point(10, 120)
            };

            mapOnlineLabel = new Label
            {
                Text = "Players on Map: 0",
                Font = new Font("Segoe UI", 12F, FontStyle.Regular, GraphicsUnit.Point),
                ForeColor = Color.Black,
                AutoSize = false,
                TextAlign = ContentAlignment.MiddleCenter,
                Dock = DockStyle.Fill
            };

            mapOnlinePanel.Controls.Add(mapOnlineLabel);
        }

        private static void InitializeMapGroupBox()
        {
            mapGroupBox = new GroupBox
            {
                Text = "Map",
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Dock = DockStyle.Fill,
                BackColor = Color.White,
                Padding = new Padding(10)
            };

            InitializeWorldMapBox();
        }

        private static void InitializeWorldMapBox()
        {
            WorldMapBox = new PictureBox
            {
                Dock = DockStyle.Fill,
                BackgroundImageLayout = ImageLayout.Zoom,
                BackColor = Color.White,
                BorderStyle = BorderStyle.None
            };

            WorldMapBox.MouseClick += WorldMapBox_MouseClick;
            WorldMapBox.MouseEnter += WorldMapBox_MouseEnter;
            WorldMapBox.MouseLeave += WorldMapBox_MouseLeave;

            loadingPictureBox = new PictureBox
            {
                Dock = DockStyle.Fill,
                BackColor = Color.White,
                SizeMode = PictureBoxSizeMode.CenterImage,
                Image = Image.FromFile("Assets/Icons/loading.gif")
            };

            mapGroupBox.Controls.Add(WorldMapBox);
            mapGroupBox.Controls.Add(loadingPictureBox);
        }

        public static async Task LoadData()
        {
            ShowLoadingAnimation();
            Config.LoadCustomCursor();
            await Task.Run(() => InitializeMap());
            HideLoadingAnimation();
        }

        private static void ShowLoadingAnimation()
        {
            if (mapGroupBox is null)
                return;

            if (loadingPictureBox is null)
                return;

            if (mapGroupBox.InvokeRequired)
            {
                mapGroupBox.Invoke(new Action(() =>
                {
                    mapGroupBox.Controls.Add(loadingPictureBox);
                    loadingPictureBox.BringToFront();
                }));
            }
            else
            {
                mapGroupBox.Controls.Add(loadingPictureBox);
                loadingPictureBox.BringToFront();
            }
        }

        private static void HideLoadingAnimation()
        {
            if (mapGroupBox is null)
                return;

            if (loadingPictureBox is null)
                return;

            if (mapGroupBox.InvokeRequired)
            {
                mapGroupBox.Invoke(new Action(() =>
                {
                    mapGroupBox.Controls.Remove(loadingPictureBox);
                    loadingPictureBox.Dispose();
                }));
            }
            else
            {
                mapGroupBox.Controls.Remove(loadingPictureBox);
                loadingPictureBox.Dispose();
            }
        }

        private static void InitalizeMapAsset()
        {
            foreach (var key in Config.avatarPaths.Keys)
            {
                Bitmap? avatarBitmap = ImageProcessor.GetIconFromUE4(Config.avatarPaths[key]);
                if (avatarBitmap == null)
                    continue;

                Bitmap? originalBgBitmap = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/frame_HUD_character_01_Sprite");
                if (originalBgBitmap == null)
                    continue;

                Bitmap bgBitmap = new Bitmap(originalBgBitmap, new Size(42, 42));
                Bitmap resizedAvatar = new Bitmap(avatarBitmap, new Size(36, 36));
                Bitmap combinedBitmap = new Bitmap(bgBitmap.Width, bgBitmap.Height);

                using (Graphics g = Graphics.FromImage(combinedBitmap))
                {
                    g.DrawImage(bgBitmap, 0, 0, bgBitmap.Width, bgBitmap.Height);

                    int avatarX = (bgBitmap.Width - resizedAvatar.Width) / 2;
                    int avatarY = (bgBitmap.Height - resizedAvatar.Height) / 2;

                    g.DrawImage(resizedAvatar, avatarX, avatarY, resizedAvatar.Width, resizedAvatar.Height);
                }

                AvatarBitmaps[key] = combinedBitmap;

                avatarBitmap.Dispose();
                resizedAvatar.Dispose();
                bgBitmap.Dispose();
                originalBgBitmap.Dispose();
            }

            if (WorldMapBox != null)
                SwitchMap(0);
            else
                Log("WorldMapBox is not initialized.");
        }

        private static async Task InitializeMap()
        {
            if (Config.IsNewVersionDetected())
            {
                Log("Loading assets ..");
                await LoadAndSaveAssets();
                InitializeTouchAreas();
                Config.SaveCacheData(CachedBitmaps, CachedMapItems, mapClickAreas);
            }
            else
            {
                Log("Loading cached assets ..");
                Config.LoadCacheData();
            }

            UpdateTotalOnline();
            InitalizeMapAsset();
        }

        private static Bitmap? GenerateMapItemView(int mapId)
        {
            if (!FileManager.ItemViewerList.TryGetValue(mapId, out var itemViewerData))
                return null;

            Bitmap newBitmap = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);
            using (Graphics graphics = Graphics.FromImage(newBitmap))
            {
                graphics.Clear(Color.Transparent);

                Color topColor = Color.FromArgb(150, 13, 18, 32);

                int bgStartX = newBitmap.Width * 70 / 100;
                int bgStartY = 168;
                int bgWidth = newBitmap.Width * 30 / 100;
                int bgHeight = newBitmap.Height - bgStartY;

                Font defaultFont = new Font("Arial", 12, FontStyle.Bold);
                Font smallerFont = new Font("Arial", 6);

                if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                {
                    defaultFont = new Font("Microsoft YaHei", 14);
                    smallerFont = new Font("Microsoft YaHei", 8);
                }

                int tabHeight = 80;

                Color lineColor = Color.FromArgb(128, 96, 96, 96);
                using (Pen linePen = new Pen(lineColor, 2))
                {
                    float padding = 100;
                    //graphics.DrawLine(linePen, bgStartX + padding, bgStartY, bgStartX + bgWidth - padding, bgStartY);
                    graphics.DrawLine(linePen, bgStartX + padding, bgStartY + tabHeight, bgStartX + bgWidth - padding, bgStartY + tabHeight);
                }

                if (FileManager.StringMessageMap.TryGetValue(1019045, out var titleText))
                {
                    using (Brush textBrush = new SolidBrush(Color.White))
                    {
                        SizeF textSize = graphics.MeasureString(titleText.Text, defaultFont);

                        float textX = bgStartX + (bgWidth - textSize.Width) / 2;
                        float textY = bgStartY + (tabHeight - textSize.Height) / 2;

                        graphics.DrawString(titleText.Text, defaultFont, textBrush, textX, textY);
                    }
                }

                ImageProcessor.DrawItemIcons(graphics, itemViewerData, bgStartX, bgStartY + tabHeight, bgWidth, bgHeight, smallerFont);
            }

            lock (CachedBitmaps)
                CachedMapItems[mapId] = newBitmap;

            Log($"Map item viewer initialized. [MapId: {mapId}]");
            return newBitmap;
        }

        public static async Task LoadAndSaveAssets()
        {
            mapClickAreas.Clear();

            Bitmap? worldMapBitmap = ImageProcessor.GetIconFromUE4(Config.worldMapDefaultIcon);
            if (worldMapBitmap is null)
            {
                Log("Failed to load world map main.");
                return;
            }

            if (!FileManager.IconPathMap.TryGetValue(Config.worldMapDefaultIcon, out Config.worldMapDefaultResource))
                return;

            var worldMapTasks = new List<Task>();

            foreach (var mapData in FileManager.MapWorldList.Values)
            {
                if (mapData.AreaMapResource == "0")
                    continue;

                worldMapTasks.Add(Task.Run(() => InitializeWorldMap(worldMapBitmap, mapData)));

                Bitmap? areaMapBitmap = ImageProcessor.GetIconFromUE4(mapData.AreaMapResource);
                if (areaMapBitmap is null)
                {
                    Log($"Failed to load area map for resource: {mapData.AreaMapResource}");
                    continue;
                }

                ParallelOptions parallelOptions = new ParallelOptions { MaxDegreeOfParallelism = Environment.ProcessorCount };

                Parallel.ForEach(FileManager.MapAreaList[mapData.AreaId], parallelOptions, areaData =>
                {
                    if (areaData.MiniGroupIcon == 0)
                        return;

                    InitializeAreaMap(areaMapBitmap, mapData, areaData);

                    Parallel.ForEach(FileManager.MapMiniList[areaData.MiniGroupId], miniData =>
                    {
                        Bitmap? miniMapBitmap = ImageProcessor.LoadTextureObject(miniData.MiniMapResource);
                        if (miniMapBitmap is null)
                        {
                            Log($"Failed to load mini map for resource: {miniData.MiniMapResource}");
                            return;
                        }

                        Task.Run(() => GenerateMapItemView(miniData.MiniStageId));

                        Bitmap background = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);
                        InitializeMiniMap(background, miniMapBitmap, mapData, areaData, miniData);

                        lock (CachedBitmaps)
                            CachedBitmaps[miniData.MiniStageId] = background;

                        Log($"Mini map initialized. [GroupId: {miniData.MiniGroupId} StageId: {miniData.MiniStageId}]");
                    });
                });

                CachedBitmaps[mapData.AreaId] = areaMapBitmap;
                Log($"Area map initialized. [AreaId: {mapData.AreaId}]");
            }

            await Task.WhenAll(worldMapTasks);

            CachedBitmaps[0] = worldMapBitmap;
            Log("World map initialized.");
        }

        private static void InitializeWorldMap(Bitmap worldMapBitmap, MapWorldData mapData)
        {
            lock (worldMapBitmap)
            {
                using (Graphics graphics = Graphics.FromImage(worldMapBitmap))
                {
                    ImageProcessor.DrawAndRecordHeaderText(graphics, Config.worldMapDefaultId);

                    float coordX = 0;
                    float coordY = 0;

                    if (mapData.AreaCoordinate is Dictionary<string, object> coordinateDict)
                    {
                        if (coordinateDict.TryGetValue("X", out var xValue))
                            coordX = Convert.ToSingle(xValue) + Config.offsetWorldMapX;
                        if (coordinateDict.TryGetValue("Y", out var yValue))
                            coordY = Convert.ToSingle(yValue) + Config.offsetWorldMapY;
                    }

                    var textSegments = new List<(string text, Font font, Color color, bool needBg)>();

                    string defaultFont = "Courier New";
                    if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                        defaultFont = "Microsoft YaHei";

                    StringTemplateData? titleText = null;

                    if (mapData.AreaId == 1000 && FileManager.StringTemplateMap.TryGetValue(910008, out titleText))
                        textSegments.Add((titleText.Text ?? string.Empty, new Font(defaultFont, 13, FontStyle.Bold), Color.White, true));
                    else if (mapData.AreaId == 19000 && FileManager.StringTemplateMap.TryGetValue(913001, out titleText))
                        textSegments.Add((titleText.Text ?? string.Empty, new Font(defaultFont, 13, FontStyle.Bold), Color.White, true));

                    if (mapData.AreaStringId != 0 && FileManager.StringTemplateMap.TryGetValue(mapData.AreaStringId, out var areaText))
                        textSegments.Add((areaText.Text ?? string.Empty, new Font(defaultFont, 12, FontStyle.Bold), Color.FromArgb(255, 140, 0), false));

                    PointF textCenterPoint = new PointF(coordX + 55, coordY + 15);
                    ImageProcessor.DrawCenteredText(graphics, textSegments, textCenterPoint, 400, 0, 5);

                    Bitmap? iconBitmap = ImageProcessor.GetIconFromUE4(mapData.AreaIconId);
                    if (iconBitmap != null)
                        graphics.DrawImage(iconBitmap, coordX, coordY, iconBitmap.Width, iconBitmap.Height);
                }
            }
        }

        private static void InitializeAreaMap(Bitmap areaMapBitmap, MapWorldData mapData, MapAreaData areaData)
        {
            lock (areaMapBitmap)
            {
                using (Graphics graphics = Graphics.FromImage(areaMapBitmap))
                {
                    ImageProcessor.DrawAndRecordHeaderText(graphics, areaData.AreaId, mapData.AreaId, mapData.AreaStringId);

                    float coordX = 0;
                    float coordY = 0;

                    if (areaData.MiniGroupCoordinate is Dictionary<string, object> coordinateDict)
                    {
                        if (coordinateDict.TryGetValue("X", out var xValue))
                            coordX = Convert.ToSingle(xValue) + Config.offsetAreaMapX;
                        if (coordinateDict.TryGetValue("Y", out var yValue))
                            coordY = Convert.ToSingle(yValue) + Config.offsetAreaMapY;
                    }

                    var textSegments = new List<(string text, Font font, Color color, bool needBg)>();

                    Font defaultFont = new Font("Arial", 11, FontStyle.Bold);
                    if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                        defaultFont = new Font("Microsoft YaHei", 11, FontStyle.Bold);

                    if (FileManager.StringTemplateMap.TryGetValue(areaData.MiniGroupStringId, out var miniGroupText))
                        textSegments.Add((miniGroupText.Text ?? string.Empty, defaultFont, Color.White, true));

                    if (areaData.MonLevelMin != 0 || areaData.MonLevelMax != 0)
                    {
                        if (FileManager.StringMessageMap.TryGetValue(1001052, out var levelText))
                        {
                            string lvlMinText = levelText.Text.Replace("{1}", areaData.MonLevelMin.ToString());
                            string lvlMaxText = levelText.Text.Replace("{1}", areaData.MonLevelMax.ToString());
                            string? txtSegment = "~";

                            if (FileManager.StringMessageMap.TryGetValue(1059269, out var segment))
                                txtSegment = segment.Text;

                            string lvlText = $"{lvlMinText}{txtSegment}{lvlMaxText}";
                            textSegments.Add((lvlText, defaultFont, Color.FromArgb(255, 223, 0), true));
                        }
                    }

                    PointF textCenterPoint = new PointF(coordX + 55, coordY - 20);
                    ImageProcessor.DrawCenteredText(graphics, textSegments, textCenterPoint, 200, 0, 0);

                    Bitmap? iconBitmap = ImageProcessor.GetIconFromUE4(areaData.MiniGroupIcon);
                    if (iconBitmap != null)
                        graphics.DrawImage(iconBitmap, coordX, coordY, iconBitmap.Width, iconBitmap.Height);
                }
            }
        }

        private static void InitializeMiniMap(Bitmap background, Bitmap miniMapBitmap, MapWorldData mapData, MapAreaData areaData, MapMiniData miniData)
        {
            lock (background)
            {
                Bitmap? bgCommonSprite = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Background/Bg_Common_01_Sprite");

                if (bgCommonSprite is null)
                    return;

                using (Graphics graphics = Graphics.FromImage(background))
                {
                    graphics.DrawImage(bgCommonSprite, 0, 0, Config.worldMapSizeX, Config.worldMapSizeY);

                    int tabX = 0;
                    int tabY = 86;
                    int scaledTabWidth = 290;
                    int scaledTabHeight = 80;
                    int backgroundWidth = background.Width;
                    int backgroundHeight = background.Height;

                    if (miniMapBitmap != null)
                    {
                        int scaledWidth = (int)(miniMapBitmap.Width * Config.zoomMiniMap);
                        int scaledHeight = (int)(miniMapBitmap.Height * Config.zoomMiniMap);

                        graphics.DrawImage(miniMapBitmap, Config.offsetMiniMapX, Config.offsetMiniMapY, scaledWidth, scaledHeight);
                    }

                    Bitmap? tabBackground = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Btn_sub_top_Sprite");

                    Color topColor = Color.FromArgb(100, 16, 20, 30);

                    using (Brush brush = new SolidBrush(topColor))
                        graphics.FillRectangle(brush, 0, 0, backgroundWidth, tabY);

                    ImageProcessor.DrawAndRecordHeaderText(graphics, miniData.MiniStageId, mapData.AreaId, mapData.AreaStringId, areaData.MiniGroupId, areaData.MiniGroupStringId);

                    if (tabBackground != null)
                    {

                        Color lineColor = Color.FromArgb(128, 96, 96, 96);
                        using (Pen linePen = new Pen(lineColor, 2))
                        {
                            graphics.DrawLine(linePen, 0, tabY, backgroundWidth, tabY);
                            graphics.DrawLine(linePen, 0, tabY + scaledTabHeight, backgroundWidth, tabY + scaledTabHeight);
                        }

                        ImageProcessor.DrawStageTabs(graphics, miniData.MiniStageId, miniData.MiniGroupId, miniData.ElliteCheck, tabX, tabY, scaledTabWidth, scaledTabHeight, tabBackground, backgroundWidth);
                    }

                    ImageProcessor.DrawRightPanel(graphics, miniData.MiniStageId, miniData.ElliteStageId, miniData.ElliteCheck, background, tabY, scaledTabHeight);
                }
            }
        }

        private static void InitializeTouchAreas()
        {
            foreach (var mapData in FileManager.MapWorldList.Values)
            {
                if (mapData.AreaIconId != 0)
                {
                    var (coordX, coordY) = GetCoordinates(mapData.AreaCoordinate);

                    RectangleF touchArea = new RectangleF(
                        coordX + mapData.TouchPosition_X,
                        coordY + mapData.TouchPosition_Y,
                        mapData.TouchScale_X,
                        mapData.TouchScale_Y);

                    if (!mapClickAreas.ContainsKey(Config.worldMapDefaultId))
                        mapClickAreas[Config.worldMapDefaultId] = new List<(RectangleF, int)>();

                    mapClickAreas[Config.worldMapDefaultId].Add((touchArea, mapData.AreaId));
                    //if (CachedBitmaps.ContainsKey(Config.worldMapDefaultId))
                    //{
                    //    using (Graphics g = Graphics.FromImage(CachedBitmaps[Config.worldMapDefaultId]))
                    //    {
                    //        g.DrawRectangle(Pens.Red, touchArea.X, touchArea.Y, touchArea.Width, touchArea.Height);
                    //    }
                    //}
                }
            }

            foreach (var areaData in FileManager.MapAreaList.Values.SelectMany(list => list))
            {
                if (areaData.MiniGroupIcon != 0)
                {
                    var (coordX, coordY) = GetCoordinates(areaData.MiniGroupCoordinate, true);

                    RectangleF touchArea = new RectangleF(
                        coordX + areaData.TouchPosition_X,
                        coordY + areaData.TouchPosition_Y,
                        areaData.TouchScale_X,
                        areaData.TouchScale_Y);

                    if (FileManager.MapMiniList.TryGetValue(areaData.MiniGroupId, out var miniDataList) && miniDataList.Count > 0)
                    {
                        var miniData = miniDataList[0];
                        if (!mapClickAreas.ContainsKey(areaData.AreaId))
                            mapClickAreas[areaData.AreaId] = new List<(RectangleF, int)>();

                        mapClickAreas[areaData.AreaId].Add((touchArea, miniData.MiniStageId));
                        //if (CachedBitmaps.ContainsKey(areaData.AreaId))
                        //{
                        //    using (Graphics g = Graphics.FromImage(CachedBitmaps[areaData.AreaId]))
                        //    {
                        //        g.DrawRectangle(Pens.Red, touchArea.X, touchArea.Y, touchArea.Width, touchArea.Height);
                        //    }
                        //}
                    }
                }
            }
        }

        private static (float X, float Y) GetCoordinates(Dictionary<string, object> coordinateDict, bool isAreaMap = false)
        {
            float coordX = 0, coordY = 0;

            if (coordinateDict != null)
            {
                if (coordinateDict.TryGetValue("X", out var xValue))
                    coordX = Convert.ToSingle(xValue) + (isAreaMap ? Config.offsetTouchAreaMapX : Config.offsetTouchWorldMapX);
                if (coordinateDict.TryGetValue("Y", out var yValue))
                    coordY = Convert.ToSingle(yValue) + (isAreaMap ? Config.offsetTouchAreaMapY : Config.offsetTouchWorldMapY);
            }

            return (coordX, coordY);
        }

        private static void WorldMapBox_MouseClick(object sender, MouseEventArgs e)
        {
            if (WorldMapBox.BackgroundImage is null)
                return;

            float imageAspectRatio = (float)WorldMapBox.BackgroundImage.Width / WorldMapBox.BackgroundImage.Height;
            float pictureBoxAspectRatio = (float)WorldMapBox.Width / WorldMapBox.Height;

            float scaleFactor;
            int offsetX = 0, offsetY = 0;

            if (imageAspectRatio > pictureBoxAspectRatio)
            {
                scaleFactor = (float)WorldMapBox.Width / WorldMapBox.BackgroundImage.Width;
                offsetY = (int)((WorldMapBox.Height - (WorldMapBox.BackgroundImage.Height * scaleFactor)) / 2);
            }
            else
            {
                scaleFactor = (float)WorldMapBox.Height / WorldMapBox.BackgroundImage.Height;
                offsetX = (int)((WorldMapBox.Width - (WorldMapBox.BackgroundImage.Width * scaleFactor)) / 2);
            }

            int imageX = (int)((e.X - offsetX) / scaleFactor);
            int imageY = (int)((e.Y - offsetY) / scaleFactor);
            // Log($"WorldMapBox_MouseClick X:{imageX} Y:{imageY}");
            DetermineNewMap(imageX, imageY);
        }

        private static void DetermineNewMap(int x, int y)
        {
            if (!mapClickAreas.TryGetValue(curMapId, out var areaList))
                return;

            for (int i = areaList.Count - 1; i >= 0; i--)
            {
                var (areaRect, targetMapId) = areaList[i];
                if (areaRect.Contains(x, y))
                {
                    SwitchMap(targetMapId);
                    break;
                }
            }
        }

        private static void SwitchMap(int mapId)
        {
            UpdateMapOnline(0);
            Bitmap? bitmap = GetMap(mapId);
            if (bitmap != null)
            {
                lock (bitmap) WorldMapBox.BackgroundImage = (Bitmap)bitmap.Clone();

                curMapId = mapId;
                return;
            }

            SwitchMap(0);
            Log($"Failed to switch map. not found mapId: {mapId}");
        }

        private static Bitmap CombineBitmaps(Bitmap baseBitmap, Bitmap overlayBitmap)
        {
            Bitmap combinedBitmap = new Bitmap(baseBitmap.Width, baseBitmap.Height);

            using (Graphics g = Graphics.FromImage(combinedBitmap))
            {
                lock (baseBitmap)
                {
                    g.DrawImage(baseBitmap, 0, 0);
                }
                lock (overlayBitmap)
                {
                    g.DrawImage(overlayBitmap, 0, 0);
                }
            }

            return combinedBitmap;
        }

        private static Bitmap? GetMap(int mapId)
        {
            if (!CachedBitmaps.ContainsKey(mapId))
                return null;

            Bitmap baseBitmap;
            lock (CachedBitmaps) baseBitmap = (Bitmap)CachedBitmaps[mapId].Clone();

            if (!CachedMapItems.ContainsKey(mapId))
                return baseBitmap;

            Bitmap itemBitmap;
            lock (CachedMapItems) itemBitmap = (Bitmap)CachedMapItems[mapId].Clone();

            Bitmap finalBitmap = CombineBitmaps(baseBitmap, itemBitmap);

            Task.Run(() =>
            {
                lock (finalBitmap) ShowMapPlayer((Bitmap)finalBitmap.Clone(), mapId);
            });

            return finalBitmap;
        }

        public static void ShowMapPlayer(Bitmap finalBitmap, int mapId)
        {
            if (!FileManager.PlayerList.TryGetValue(mapId, out var playerList))
                return;

            var mapMiniData = GetMapMiniDataByMapId(mapId);
            if (mapMiniData == null)
                return;

            float coordXLT = 0, coordYLT = 0;
            float coordXRB = 0, coordYRB = 0;

            if (mapMiniData.MiniMapCoordinateLT is Dictionary<string, object> coordinateDictLT)
            {
                if (coordinateDictLT.TryGetValue("X", out var xValueLT))
                    coordXLT = Convert.ToSingle(xValueLT);

                if (coordinateDictLT.TryGetValue("Y", out var yValueLT))
                    coordYLT = Convert.ToSingle(yValueLT);
            }

            if (mapMiniData.MiniMapCoordinateRB is Dictionary<string, object> coordinateDictRB)
            {
                if (coordinateDictRB.TryGetValue("X", out var xValueRB))
                    coordXRB = Convert.ToSingle(xValueRB);

                if (coordinateDictRB.TryGetValue("Y", out var yValueRB))
                    coordYRB = Convert.ToSingle(yValueRB);
            }

            int mapOnline = 0;
            Bitmap newBitmap = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);
            using (Graphics graphics = Graphics.FromImage(newBitmap))
            {
                foreach (var playerData in playerList)
                {
                    (double playerX, double playerY) = ConvertWorldToScreen(mapId, playerData.PositionX, playerData.PositionY, coordXLT, coordYLT, coordXRB, coordYRB);

                    int screenX = (int)Math.Round(playerX);
                    int screenY = (int)Math.Round(playerY);

                    if (!AvatarBitmaps.ContainsKey(playerData.Class))
                    {
                        Log($"Avatar for class ID {playerData.Class} not found.");
                        continue;
                    }

                    Bitmap avatarBitmap = AvatarBitmaps[playerData.Class];
                    int avatarWidth = avatarBitmap.Width;
                    int avatarHeight = avatarBitmap.Height;

                    lock (avatarBitmap) graphics.DrawImage(avatarBitmap, new Rectangle(screenX - avatarWidth / 2, screenY - avatarHeight / 2, avatarWidth, avatarHeight));
                    mapOnline++;
                    // Log($"MapId: {mapId} Add Player [WorldMap_X: {playerData.PositionX} WorldMap_Y: {playerData.PositionY} PoxX: {screenX}, PosY: {screenY}]");
                }
            }

            lock (finalBitmap) WorldMapBox.BackgroundImage = CombineBitmaps(finalBitmap, newBitmap);
            UpdateMapOnline(mapOnline);
        }

        private static (double, double) ConvertWorldToScreen(int mapId, double worldX, double worldY, double coordXLT, double coordYLT, double coordXRB, double coordYRB)
        {
            double bitmapWidth = 512 * Config.zoomMiniMap, bitmapHeight = 512 * Config.zoomMiniMap;

            if (coordXLT > coordXRB || coordYLT > coordYRB)
                return (0, 0);

            double scaleX = bitmapWidth / (coordXRB - coordXLT);
            double scaleY = bitmapHeight / (coordYRB - coordYLT);

            double bitmapX = (worldX - coordXLT) * scaleX;
            double bitmapY = (worldY - coordYLT) * scaleY;

            bitmapX += Config.offsetMiniMapX;
            bitmapY += Config.offsetMiniMapY;

            return (bitmapX, bitmapY);
        }

        private static MapMiniData? GetMapMiniDataByMapId(int mapId)
        {
            foreach (var entry in FileManager.MapMiniList)
            {
                foreach (var mapData in entry.Value)
                {
                    if (mapData.MiniStageId == mapId)
                        return mapData;
                }
            }

            return null;
        }

        public static void UpdateTotalOnline()
        {
            if (totalOnlineLabel == null)
                return;

            if (totalOnlineLabel.InvokeRequired)
            {
                totalOnlineLabel.Invoke(new Action(() =>
                {
                    totalOnlineLabel.Text = $"Total Online: {AllPlayerCount}";
                }));
            }
            else
            {
                totalOnlineLabel.Text = $"Total Online: {AllPlayerCount}";
            }
        }

        public static void UpdateMapOnline(int mapOnline)
        {
            if (mapOnlineLabel == null)
                return;

            if (mapOnlineLabel.InvokeRequired)
            {
                mapOnlineLabel.Invoke(new Action(() =>
                {
                    mapOnlineLabel.Text = $"Map Online: {mapOnline}";
                }));
            }
            else
            {
                mapOnlineLabel.Text = $"Map Online: {mapOnline}";
            }
        }

        private static void WorldMapBox_MouseEnter(object sender, EventArgs e)
        {
            Control? control = sender as Control;
            if (control != null)
                control.Cursor = Config.customCursor ?? Cursors.Default;
        }

        private static void WorldMapBox_MouseLeave(object sender, EventArgs e)
        {
            Control? control = sender as Control;
            if (control != null)
                control.Cursor = Cursors.Default;
        }
    }
}
