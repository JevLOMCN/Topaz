using System.ComponentModel;
using System.Drawing.Imaging;
using System.Reflection;

namespace Server_Console.Database_Tool
{
    public static class MapPage
    {
        private static Guid _instanceId;
        private static void Log(string message) => DatabaseTool.Log(_instanceId, message);
        private static PictureBox? WorldMapBox;
        private static GroupBox? serverInfoGroupBox;
        private static GroupBox? mapGroupBox;
        private static GroupBox? optionsGroupBox;
        private static Panel? leftPanel;
        private static Panel? totalPlayerPanel;
        private static Panel? totalOnlinePanel;
        private static Panel? mapOnlinePanel;
        private static Label? totalPlayerLabel;
        private static Label? totalOnlineLabel;
        private static Label? mapOnlineLabel;
        private static PictureBox? loadingPictureBox;
        private static ComboBox? playerSearchComboBox;
        private static ComboBox? mapSearchComboBox;
        private static Button? refreshPlayerDataButton;
        private static CheckBox? autoRefreshCheckBox;
        private static Label? countdownLabel;
        private static System.Windows.Forms.Timer? autoRefreshTimer;

        private static int autoRefreshInterval = Config.playerDataAutoRefreshInterval * 1000;
        private static int countdownTime = Config.playerDataAutoRefreshInterval;

        private static bool ShowPlayer = true;
        private static bool ShowMerchantNpc = false;
        private static bool ShowResidentNpc = false;
        private static bool ShowWayPoint = false;
        private static bool ShowGathering = false;
        private static bool ShowMonster = true;
        private static bool ShowDemonSpawnPoint = false;

        private static int curMapId = 0;
        public static int AllPlayerCount = 0;
        public static int AllOnlineCount = 0;

        private static string CurrentSearchPlayer = string.Empty;

        public static Bitmap? Bitmap_BgText;
        public static Bitmap? Bitmap_TabBackground;
        public static Bitmap? Bitmap_BgCommonSprite;
        public static Bitmap? Bitmap_NextIcon;

        public static List<SpecialMapData> SpecialMap = new List<SpecialMapData>();
        public static Dictionary<int, Bitmap> CachedBitmaps = new Dictionary<int, Bitmap>();
        public static Dictionary<int, Bitmap> CachedMapItems = new Dictionary<int, Bitmap>();
        public static Dictionary<int, List<(RectangleF areaRect, int targetMapId)>> mapClickAreas = new Dictionary<int, List<(RectangleF, int)>>();
        public static readonly Dictionary<int, Bitmap> AvatarBitmaps = new Dictionary<int, Bitmap>();
        public static readonly Dictionary<string, Bitmap> MiniMapIconBitmaps = new Dictionary<string, Bitmap>();

        public static void Initialize(Guid instanceId, Control container)
        {
            _instanceId = instanceId;
            var tabPage = new Panel
            {
                Name = "Map Page",
                Padding = new Padding(3),
                Size = new Size(1400, 600),
                TabIndex = 1,
                Location = new Point(4, 0),
                BackColor = Color.White
            };

            InitializeComponent(tabPage);
            container.Controls.Add(tabPage);
        }

        private static void InitializeComponent(Panel tabPage)
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
                Text = FileManager.GetStringMessageById(1071515),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Dock = DockStyle.Top,
                Height = 160,
                BackColor = Color.White,
                Padding = new Padding(10)
            };

            InitializeTotalPlayerPanel();
            InitializeTotalOnlinePanel();
            InitializeMapOnlinePanel();

            serverInfoGroupBox.Controls.Add(totalPlayerPanel);
            serverInfoGroupBox.Controls.Add(totalOnlinePanel);
            serverInfoGroupBox.Controls.Add(mapOnlinePanel);
        }

        private static void InitializeOptionsGroupBox()
        {
            optionsGroupBox = new GroupBox
            {
                Text = FileManager.GetStringTemplateById(1020149),
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
            int startX = 20;
            int startY = 30;
            int spacing = 25;
            int extraSpacing = 35;

            refreshPlayerDataButton = LoadRefreshPlayerDataButton(startX, startY);
            refreshPlayerDataButton.Click += (s, e) => RefreshPlayerData();

            InitializeTimer();

            ComboBox classComboBox = LoadClassComboBox(startX, startY += extraSpacing);

            CheckBox showPlayerCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 2618029, 1086612),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += extraSpacing),
                Checked = ShowPlayer,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showPlayerCheckBox.CheckedChanged += (s, e) => ToggleShowPlayer(showPlayerCheckBox.Checked);

            CheckBox showMerchantNpcCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019040),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowMerchantNpc,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showMerchantNpcCheckBox.CheckedChanged += (s, e) => ToggleShowMerchantNpc(showMerchantNpcCheckBox.Checked);

            CheckBox showResidentNpcCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019051),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowResidentNpc,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showResidentNpcCheckBox.CheckedChanged += (s, e) => ToggleShowResidentNpc(showResidentNpcCheckBox.Checked);

            CheckBox showWayPointCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019041),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowWayPoint,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showWayPointCheckBox.CheckedChanged += (s, e) => ToggleShowWayPoint(showWayPointCheckBox.Checked);

            CheckBox showGatheringCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019042),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowGathering,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showGatheringCheckBox.CheckedChanged += (s, e) => ToggleShowGathering(showGatheringCheckBox.Checked);

            CheckBox showMonsterCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019043),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowMonster,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showMonsterCheckBox.CheckedChanged += (s, e) => ToggleShowMonster(showMonsterCheckBox.Checked);

            CheckBox showDemonSpawnPointCheckBox = new CheckBox
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1033104, 1019611),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                AutoSize = true,
                Location = new Point(startX, startY += spacing),
                Checked = ShowDemonSpawnPoint,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            showDemonSpawnPointCheckBox.CheckedChanged += (s, e) => ToggleShowDemonSpawnPoint(showDemonSpawnPointCheckBox.Checked);

            mapSearchComboBox = LoadMapSearchComboBox(ref startX, ref startY, extraSpacing);
            Button switchMapButton = LoadSwitchMapButton(startY);
            switchMapButton.Click += (s, e) => EnterMap(mapSearchComboBox);

            playerSearchComboBox = LoadPlayerSearchComboBox(ref startX, ref startY, extraSpacing);
            Button searchPlayerButton = LoadSearchPlayerButton(startY);
            searchPlayerButton.Click += (s, e) => SearchPlayer(playerSearchComboBox);

            if (optionsGroupBox is null)
                return;

            optionsGroupBox.Controls.Add(classComboBox);
            optionsGroupBox.Controls.Add(showPlayerCheckBox);
            optionsGroupBox.Controls.Add(showMerchantNpcCheckBox);
            optionsGroupBox.Controls.Add(showResidentNpcCheckBox);
            optionsGroupBox.Controls.Add(showWayPointCheckBox);
            optionsGroupBox.Controls.Add(showGatheringCheckBox);
            optionsGroupBox.Controls.Add(showMonsterCheckBox);
            optionsGroupBox.Controls.Add(showDemonSpawnPointCheckBox);
            optionsGroupBox.Controls.Add(mapSearchComboBox);
            optionsGroupBox.Controls.Add(switchMapButton);
            optionsGroupBox.Controls.Add(playerSearchComboBox);
            optionsGroupBox.Controls.Add(searchPlayerButton);
            optionsGroupBox.Controls.Add(refreshPlayerDataButton);
        }

        private static void InitializeTimer()
        {
            if (autoRefreshTimer != null)
            {
                autoRefreshTimer.Stop();
                autoRefreshTimer.Dispose();
            }

            autoRefreshTimer = new System.Windows.Forms.Timer
            {
                Interval = 1000
            };

            autoRefreshTimer.Tick += (s, e) => AutoRefreshTick();
            autoRefreshTimer.Start();
        }

        private static Button LoadRefreshPlayerDataButton(int startX, int startY)
        {
            return new Button
            {
                Text = GetRefreshPlayerDataButtonText(),
                Font = new Font("Segoe UI", 9F),
                Location = new Point(startX, startY),
                Width = 250,
                Height = 22,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
        }

        private static ComboBox LoadClassComboBox(int startX, int startY)
        {
            ComboBox classComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(startX, startY),
                Width = 250,
                DropDownStyle = ComboBoxStyle.DropDownList,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            foreach (var key in Config.avatarPaths.Keys)
            {
                var (_, stringId) = Config.avatarPaths[key];
                string professionName = FileManager.GetStringTemplateById(stringId);

                if (!string.IsNullOrEmpty(professionName))
                    classComboBox.Items.Add(professionName);
            }

            classComboBox.SelectedIndex = Math.Max(0, Config.worldMapDefaultClassId - 1);
            return classComboBox;
        }

        private static ComboBox LoadMapSearchComboBox(ref int startX, ref int startY, int extraSpacing)
        {
            mapSearchComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(startX, startY += extraSpacing),
                Width = 180,
                DropDownStyle = ComboBoxStyle.DropDown,
                Anchor = AnchorStyles.Top | AnchorStyles.Left,
            };

            mapSearchComboBox.SelectedIndexChanged += mapSearchComboBox_SelectedIndexChanged;
            mapSearchComboBox.TextChanged += mapSearchComboBox_TextChanged;

            return mapSearchComboBox;
        }

        private static ComboBox LoadPlayerSearchComboBox(ref int startX, ref int startY, int extraSpacing)
        {
            playerSearchComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(startX, startY += extraSpacing - 5),
                Width = 180,
                DropDownStyle = ComboBoxStyle.DropDown,
                Anchor = AnchorStyles.Top | AnchorStyles.Left,
            };

            playerSearchComboBox.SelectedIndexChanged += playerSearchComboBox_SelectedIndexChanged;
            playerSearchComboBox.TextChanged += playerSearchComboBox_TextChanged;

            return playerSearchComboBox;
        }

        private static Button LoadSwitchMapButton(int startY)
        {
            Button switchMapButton = new Button
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1088025, 1019001),
                Font = new Font("Segoe UI", 9F),
                Location = new Point(210, startY),
                Width = 70,
                Height = 22,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            return switchMapButton;
        }

        private static Button LoadSearchPlayerButton(int startY)
        {
            Button searchPlayerButton = new Button
            {
                Text = FileManager.CombineStringsWithSpaces(FileManager.GetStringTemplateById, 1000126, 5200009),
                Font = new Font("Segoe UI", 9F),
                Location = new Point(210, startY),
                Width = 70,
                Height = 22,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            return searchPlayerButton;
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

        private static void InitializeTotalPlayerPanel()
        {
            totalPlayerPanel = new Panel
            {
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Size = new Size(280, 30),
                Location = new Point(10, 30)
            };

            totalPlayerLabel = new Label
            {
                Text = GetPlayerPanelDesc(1),
                Font = new Font("Segoe UI", 12F, FontStyle.Regular, GraphicsUnit.Point),
                ForeColor = Color.Black,
                AutoSize = false,
                TextAlign = ContentAlignment.MiddleCenter,
                Dock = DockStyle.Fill
            };

            totalPlayerPanel.Controls.Add(totalPlayerLabel);
        }

        private static void InitializeTotalOnlinePanel()
        {
            totalOnlinePanel = new Panel
            {
                BackColor = Color.White,
                BorderStyle = BorderStyle.FixedSingle,
                Size = new Size(280, 30),
                Location = new Point(10, 70)
            };

            totalOnlineLabel = new Label
            {
                Text = GetPlayerPanelDesc(2),
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
                Size = new Size(280, 30),
                Location = new Point(10, 110)
            };

            mapOnlineLabel = new Label
            {
                Text = GetPlayerPanelDesc(3),
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
                Text = FileManager.GetStringMessageById(1019001),
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

            if (mapGroupBox is null) 
                return;

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

        private static void InitializeMapAsset()
        {
            InitializePlayerAvatar();
            InitializeMiniMapIcons();
            if (WorldMapBox != null)
                SwitchMap(0);
            else
                Log("WorldMapBox is not initialized.");
        }

        private static void InitializePlayerAvatar()
        {
            foreach (var key in Config.avatarPaths.Keys)
            {
                var (avatarPath, _) = Config.avatarPaths[key];
                using (Bitmap? avatarBitmap = ImageProcessor.GetIconFromUE4(avatarPath),
                                originalBgBitmap = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/frame_HUD_character_01_Sprite"),
                                selectedBgBitmap = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/Skill_Slot_Main_Sprite"))
                {
                    if (avatarBitmap == null || originalBgBitmap == null || selectedBgBitmap == null)
                        continue;

                    using (Bitmap resizedAvatar = new Bitmap(avatarBitmap, new Size(32, 32)),
                                  resizedSelectedAvatar = new Bitmap(avatarBitmap, new Size(40, 40)))
                    {
                        using (Bitmap resizedOriginalBg = new Bitmap(originalBgBitmap, new Size(40, 40)),
                                      resizedSelectedBg = new Bitmap(selectedBgBitmap, new Size(64, 64)))
                        {
                            Bitmap combinedBitmap = CombineAvatarWithBackground(resizedOriginalBg, resizedAvatar);
                            AvatarBitmaps[key] = combinedBitmap;

                            Bitmap selectedCombinedBitmap = CombineAvatarWithBackground(resizedSelectedBg, resizedSelectedAvatar);
                            AvatarBitmaps[key + 10] = selectedCombinedBitmap;
                        }
                    }
                }
            }
        }

        private static Bitmap CombineAvatarWithBackground(Bitmap bgBitmap, Bitmap resizedAvatar)
        {
            Bitmap combinedBitmap = new Bitmap(bgBitmap.Width, bgBitmap.Height);
            using (Graphics g = Graphics.FromImage(combinedBitmap))
            {
                g.DrawImage(bgBitmap, 0, 0, bgBitmap.Width, bgBitmap.Height);

                int avatarX = (bgBitmap.Width - resizedAvatar.Width) / 2;
                int avatarY = (bgBitmap.Height - resizedAvatar.Height) / 2;

                g.DrawImage(resizedAvatar, avatarX, avatarY, resizedAvatar.Width, resizedAvatar.Height);
            }
            return combinedBitmap;
        }

        private static void InitializeMiniMapIcons()
        {
            foreach (var key in Config.miniMapIconPaths.Keys)
            {
                var (iconPath, scaleFactor) = Config.miniMapIconPaths[key];

                Bitmap? iconBitmap = ImageProcessor.GetIconFromUE4(iconPath);
                if (iconBitmap == null)
                    continue;

                if (scaleFactor != 1)
                {
                    int newWidth = (int)(iconBitmap.Width * scaleFactor);
                    int newHeight = (int)(iconBitmap.Height * scaleFactor);

                    Bitmap enlargedIcon = new Bitmap(newWidth, newHeight);
                    using (Graphics graphics = Graphics.FromImage(enlargedIcon))
                    {
                        graphics.InterpolationMode = System.Drawing.Drawing2D.InterpolationMode.HighQualityBicubic;
                        graphics.DrawImage(iconBitmap, new Rectangle(0, 0, newWidth, newHeight));
                    }

                    MiniMapIconBitmaps[key] = enlargedIcon;
                }
                else
                {
                    MiniMapIconBitmaps[key] = iconBitmap;
                }
            }
        }

        private static async Task InitializeMap()
        {
            await InitializeBitmapAsset();
            if (Config.IsNewVersionDetected("Assets/Paks", "PaksHash", Config.mapCacheFileName))
            {
                Log("Loading map assets ..");
                await LoadAndSaveAssets();
                InitializeTouchAreas();
                Config.SaveCacheData(Config.mapCacheFileName, CachedBitmaps, CachedMapItems, mapClickAreas);
            }
            else
            {
                Log("Loading map cached assets ..");
                Config.LoadCacheData(Config.mapCacheFileName);
            }

            UpdatePlayerPanel(totalPlayerLabel, 1, AllPlayerCount);
            UpdatePlayerPanel(totalOnlineLabel, 2, AllOnlineCount);
            InitializeMapAsset();
            Log("Loaded map cached data.");
        }

        private static async Task InitializeBitmapAsset()
        {
            Bitmap_BgText = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/Frame_txt_Sprite");
            Bitmap_TabBackground = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Btn_sub_top_Sprite");
            Bitmap_BgCommonSprite = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Background/Bg_Common_01_Sprite");
            Bitmap_NextIcon = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Icon_next_Big_Sprite");
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

                Font defaultFont = ImageProcessor.GetFont("Arial", 12, FontStyle.Bold);
                Font smallerFont = ImageProcessor.GetFont("Arial", 6);

                if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                {
                    defaultFont = ImageProcessor.GetFont("Microsoft YaHei", 14);
                    smallerFont = ImageProcessor.GetFont("Microsoft YaHei", 8);
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

            ParallelOptions parallelOptions = new ParallelOptions { MaxDegreeOfParallelism = Environment.ProcessorCount };

            var worldMapTasks = new List<Task>();

            foreach (var mapData in FileManager.MapWorldList.Values)
            {
                Bitmap? areaMapBitmap = null;
                if (mapData.AreaMapResource != "0")
                {
                    areaMapBitmap = ImageProcessor.GetIconFromUE4(mapData.AreaMapResource);
                    if (areaMapBitmap is null)
                    {
                        Log($"Failed to load area map for resource: {mapData.AreaMapResource}");
                        continue;
                    }
                }

                var worldMapTask = Task.Run(() => InitializeWorldMap(worldMapBitmap, mapData));
                if (worldMapTask != null)
                {
                    worldMapTasks.Add(worldMapTask);
                }

                if (FileManager.MapAreaList.ContainsKey(mapData.AreaId))
                {
                    var areaTasks = new List<Task>();
                    Parallel.ForEach(FileManager.MapAreaList[mapData.AreaId], parallelOptions, areaData =>
                    {
                        if (areaMapBitmap is null || areaData.MiniGroupIcon == 0)
                            return;

                        InitializeAreaMap(areaMapBitmap, mapData, areaData);

                        var miniTasks = new List<Task>();

                        Parallel.ForEach(FileManager.MapMiniList[areaData.MiniGroupId], miniData =>
                        {
                            var miniTask = Task.Run(() =>
                            {
                                if (areaData.MiniGroupIcon == 0)
                                    return;

                                Bitmap? miniMapBitmap = ImageProcessor.LoadTextureObject(miniData.MiniMapResource);
                                if (miniMapBitmap is null)
                                {
                                    Log($"Failed to load mini map for resource: {miniData.MiniMapResource}");
                                    return;
                                }

                                Bitmap background = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);
                                InitializeMiniMap(background, miniMapBitmap, mapData, areaData, miniData);
                                GenerateMapItemView(miniData.MiniStageId);

                                lock (CachedBitmaps)
                                    CachedBitmaps[miniData.MiniStageId] = background;

                                Log($"Mini map initialized. [GroupId: {miniData.MiniGroupId} StageId: {miniData.MiniStageId}]");
                            });

                            if (miniTask != null)
                            {
                                miniTasks.Add(miniTask);
                            }
                        });

                        if (miniTasks.Any())
                            areaTasks.Add(Task.WhenAll(miniTasks));
                    });

                    if (areaTasks.Any())
                        await Task.WhenAll(areaTasks.Where(task => task != null));
                }

                if (areaMapBitmap is null)
                    continue;

                CachedBitmaps[mapData.AreaId] = areaMapBitmap;
                Log($"Area map initialized. [AreaId: {mapData.AreaId}]");
            }

            var specialTasks = new List<Task>();
            Parallel.ForEach(FileManager.SpecialMapList, parallelOptions, stageEntry =>
            {
                var stageId = stageEntry.Key;
                if (!CachedBitmaps.ContainsKey(stageId))
                {
                    if (FileManager.MapStageMiniList.TryGetValue(stageId, out var miniDataEntry))
                    {
                        var specialTask = Task.Run(() =>
                        {
                            Bitmap? miniMapBitmap = ImageProcessor.LoadTextureObject(miniDataEntry.MiniMapResource);
                            if (miniMapBitmap is null)
                            {
                                Log($"Failed to load special map for resource: {miniDataEntry.MiniMapResource}");
                                return;
                            }

                            Bitmap background = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);

                            FileManager.MapGroupAreaList.TryGetValue(miniDataEntry.MiniGroupId, out var groupDataEntry);
                            FileManager.MapWorldList.TryGetValue(groupDataEntry?.AreaId ?? -1, out var worldDataEntry);

                            InitializeMiniMap(background, miniMapBitmap, worldDataEntry, null, miniDataEntry);
                            GenerateMapItemView(stageId);

                            lock (CachedBitmaps)
                                CachedBitmaps[stageId] = background;

                            Log($"Special map initialized. [StageId: {stageId}, Type: {stageEntry.Value.StageType}]");
                        });

                        if (specialTask != null)
                        {
                            specialTasks.Add(specialTask);
                        }
                    }
                }
            });

            if (specialTasks.Any())
                await Task.WhenAll(specialTasks.Where(task => task != null));

            if (worldMapTasks.Any())
                await Task.WhenAll(worldMapTasks.Where(task => task != null));

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

                        if (Convert.ToSingle(xValue) <= 0 || Convert.ToSingle(yValue) <= 0)
                            return;
                    }
                    var textSegments = new List<(string text, Font font, Color color, bool needBg)>();

                    string defaultFont = "Courier New";
                    if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                        defaultFont = "Microsoft YaHei";

                    StringTemplateData? titleText = null;

                    if (mapData.AreaId == 1000 && FileManager.StringTemplateMap.TryGetValue(910008, out titleText))
                        textSegments.Add((titleText.Text ?? string.Empty, ImageProcessor.GetFont(defaultFont, 13, FontStyle.Bold), Color.White, true));
                    else if (mapData.AreaId == 19000 && FileManager.StringTemplateMap.TryGetValue(913001, out titleText))
                        textSegments.Add((titleText.Text ?? string.Empty, ImageProcessor.GetFont(defaultFont, 13, FontStyle.Bold), Color.White, true));

                    if (mapData.AreaStringId != 0 && FileManager.StringTemplateMap.TryGetValue(mapData.AreaStringId, out var areaText))
                        textSegments.Add((areaText.Text ?? string.Empty, ImageProcessor.GetFont(defaultFont, 12, FontStyle.Bold), Color.FromArgb(255, 140, 0), false));

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

                        if (Convert.ToSingle(xValue) <= 0 || Convert.ToSingle(yValue) <= 0)
                            return;
                    }

                    var textSegments = new List<(string text, Font font, Color color, bool needBg)>();

                    Font defaultFont = ImageProcessor.GetFont("Arial", 11, FontStyle.Bold);
                    if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                        defaultFont = ImageProcessor.GetFont("Microsoft YaHei", 11, FontStyle.Bold);

                    if (FileManager.StringTemplateMap.TryGetValue(areaData.MiniGroupStringId, out var miniGroupText))
                        textSegments.Add((miniGroupText.Text ?? string.Empty, defaultFont, Color.White, true));

                    if (areaData.MonLevelMin != 0 || areaData.MonLevelMax != 0)
                    {
                        if (FileManager.StringMessageMap.TryGetValue(1001052, out var levelText))
                        {
                            string lvlMinText = GetFormatString(levelText.Text, areaData.MonLevelMin.ToString());
                            string lvlMaxText = GetFormatString(levelText.Text, areaData.MonLevelMax.ToString());
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

        private static void InitializeMiniMap(Bitmap background, Bitmap miniMapBitmap, MapWorldData? mapData, MapAreaData? areaData, MapMiniData miniData)
        {
            lock (background)
            {
                if (Bitmap_BgCommonSprite is null)
                    return;

                using (Graphics graphics = Graphics.FromImage(background))
                {
                    lock (Bitmap_BgCommonSprite)
                        graphics.DrawImage(Bitmap_BgCommonSprite, 0, 0, Config.worldMapSizeX, Config.worldMapSizeY);

                    int tabX = 0;
                    int tabY = 86;
                    int scaledTabWidth = 291;
                    int scaledTabHeight = 80;
                    int backgroundWidth = background.Width;
                    int backgroundHeight = background.Height;

                    if (miniMapBitmap != null)
                    {
                        Bitmap transparentMiniMap = SetBitmapOpacity(miniMapBitmap, 144);

                        int scaledWidth = (int)(transparentMiniMap.Width * Config.zoomMiniMap);
                        int scaledHeight = (int)(transparentMiniMap.Height * Config.zoomMiniMap);

                        graphics.DrawImage(transparentMiniMap, Config.offsetMiniMapX, Config.offsetMiniMapY, scaledWidth, scaledHeight);
                    }

                    Color topColor = Color.FromArgb(100, 16, 20, 30);

                    using (Brush brush = new SolidBrush(topColor))
                        graphics.FillRectangle(brush, 0, 0, backgroundWidth, tabY);

                    ImageProcessor.DrawAndRecordHeaderText(graphics, miniData.MiniStageId, mapData?.AreaId ?? 0, mapData?.AreaStringId ?? 0, areaData?.MiniGroupId ?? 0, areaData?.MiniGroupStringId ?? 0);

                    if (Bitmap_TabBackground != null)
                    {
                        Color lineColor = Color.FromArgb(128, 96, 96, 96);
                        using (Pen linePen = new Pen(lineColor, 2))
                        {
                            graphics.DrawLine(linePen, 0, tabY, backgroundWidth, tabY);
                            graphics.DrawLine(linePen, 0, tabY + scaledTabHeight, backgroundWidth, tabY + scaledTabHeight);
                        }

                        ImageProcessor.DrawStageTabs(graphics, miniData.MiniStageId, miniData.MiniGroupId, miniData.ElliteCheck, tabX, tabY, scaledTabWidth, scaledTabHeight, Bitmap_TabBackground, backgroundWidth);
                    }

                    ImageProcessor.DrawRightPanel(graphics, miniData.MiniStageId, miniData.ElliteStageId, miniData.ElliteCheck, background, tabY, scaledTabHeight);
                }
            }
        }

        private static Bitmap SetBitmapOpacity(Bitmap bitmap, int opacity)
        {
            Bitmap newBitmap = new Bitmap(bitmap.Width, bitmap.Height);
            using (Graphics g = Graphics.FromImage(newBitmap))
            {
                ColorMatrix matrix = new ColorMatrix();
                matrix.Matrix33 = opacity / 255f;

                ImageAttributes attributes = new ImageAttributes();
                attributes.SetColorMatrix(matrix, ColorMatrixFlag.Default, ColorAdjustType.Bitmap);

                g.DrawImage(bitmap, new Rectangle(0, 0, bitmap.Width, bitmap.Height), 0, 0, bitmap.Width, bitmap.Height, GraphicsUnit.Pixel, attributes);
            }
            return newBitmap;
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

        private static void WorldMapBox_MouseClick(object? sender, MouseEventArgs e)
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
            UpdatePlayerPanel(mapOnlineLabel, 3, 0);
            Bitmap? bitmap = GetMap(mapId);
            if (bitmap != null)
            {
                lock (bitmap) WorldMapBox.BackgroundImage = (Bitmap)bitmap.Clone();
                curMapId = mapId;
                // Log($"Switch to map: {mapId}");
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
                lock (finalBitmap) ShowMapDetail((Bitmap)finalBitmap.Clone(), mapId);
            });

            return finalBitmap;
        }

        public static void ShowMapDetail(Bitmap finalBitmap, int mapId)
        {
            Bitmap newBitmap = new Bitmap(Config.worldMapSizeX, Config.worldMapSizeY);

            (float coordXLT, float coordYLT, float coordXRB, float coordYRB) = GetCoordinateLTRB(mapId);

            using (Graphics graphics = Graphics.FromImage(newBitmap))
            {
                DrawMapSectorGrade(graphics, mapId, coordXLT, coordYLT, coordXRB, coordYRB);

                if (FileManager.MapMiniInfoList.TryGetValue(mapId, out var miniInfoList))
                {
                    foreach (var data in miniInfoList)
                    {
                        if (data is MapMiniInfoData miniInfoData)
                        {
                            var coordObject = miniInfoData.InfoCoordinate;

                            if (coordObject != null &&
                                coordObject.TryGetValue("X", out var xObj) &&
                                coordObject.TryGetValue("Y", out var yObj) &&
                                double.TryParse(xObj.ToString(), out double posX) &&
                                double.TryParse(yObj.ToString(), out double posY))
                            {
                                (double screenPosX, double screenPosY) = ConvertWorldToScreen(mapId, posX, posY, coordXLT, coordYLT, coordXRB, coordYRB);
                                int screenX = (int)Math.Round(screenPosX);
                                int screenY = (int)Math.Round(screenPosY);

                                int infoSubType = miniInfoData.InfoSubType;
                                int infoSubTypeValue = miniInfoData.InfoSubTypeValue;

                                if (infoSubType == 1 && ShowWayPoint is false)
                                    continue;
                                else if (infoSubType == 2 && infoSubTypeValue != 0 && ShowMonster is false)
                                    continue;
                                else if (infoSubType == 3 && infoSubTypeValue != 0 && ShowGathering is false)
                                    continue;
                                else if (infoSubType == 4 && ShowMerchantNpc is false)
                                    continue;
                                else if (infoSubType == 5 && ShowResidentNpc is false)
                                    continue;
                                else if (infoSubType == 6 && ShowDemonSpawnPoint is false)
                                    continue;

                                ImageProcessor.DrawMiniMapIcon(graphics, miniInfoData, screenX, screenY);

                                string iconKey = GetIconKeyByTypeAndValue(infoSubType, infoSubTypeValue);
                                if (MiniMapIconBitmaps.ContainsKey(iconKey))
                                {
                                    Bitmap iconBitmap = MiniMapIconBitmaps[iconKey];
                                    ImageProcessor.DrawMiniMapString(graphics, miniInfoData, screenX, screenY, iconBitmap.Height);
                                }
                            }
                        }
                    }
                }
            }

            newBitmap = ShowMapPlayer(newBitmap, mapId);
            lock (finalBitmap) WorldMapBox.BackgroundImage = CombineBitmaps(finalBitmap, newBitmap);
        }

        private static void DrawMapSectorGrade(Graphics graphics, int mapId, float coordXLT, float coordYLT, float coordXRB, float coordYRB)
        {
            if (!FileManager.MapSectorGradeList.TryGetValue(mapId, out var sectorList))
                return;

            float scaleX = Config.worldMapSizeX / (coordXRB - coordXLT);
            float scaleY = Config.worldMapSizeY / (coordYRB - coordYLT);

            foreach (var sectorData in sectorList)
            {
                var sectorLocation = sectorData.SectorLocation;
                if (sectorLocation == null)
                    continue;

                if (sectorLocation.TryGetValue("X", out var xObj) &&
                    sectorLocation.TryGetValue("Y", out var yObj) &&
                    double.TryParse(xObj.ToString(), out double posX) &&
                    double.TryParse(yObj.ToString(), out double posY))
                {
                    (double screenPosX, double screenPosY) = ConvertWorldToScreen(mapId, posX, posY, coordXLT, coordYLT, coordXRB, coordYRB);
                    int screenX = (int)Math.Round(screenPosX);
                    int screenY = (int)Math.Round(screenPosY);

                    float sectorRange = sectorData.SectorRange;

                    float radius = sectorRange * (scaleX + scaleY) / (float)4.25;

                    if (!FileManager.MapStageSectorList.TryGetValue(sectorData.SectorID, out var stageSectorData))
                        continue;

                    byte alpha = 0x7F;
                    if (mapId == 101003010)
                        alpha = 0x48;

                    Color? sectorColor = stageSectorData.SectorGrade switch
                    {
                        1 => Color.FromArgb(alpha, 0x00, 0xDB, 0x54),
                        2 => Color.FromArgb(alpha, 0x00, 0x61, 0xE7),
                        3 => Color.FromArgb(alpha, 0xFF, 0x28, 0x6C),
                        4 => Color.FromArgb(alpha, 0xFF, 0xEC, 0x32),
                        _ => null,
                    };

                    if (sectorColor.HasValue)
                    {
                        using (SolidBrush brush = new SolidBrush(sectorColor.Value))
                            graphics.FillEllipse(brush, screenX - radius, screenY - radius, radius * 2, radius * 2);
                    }
                }
            }
        }

        public static string GetIconKeyByTypeAndValue(int infoSubType, int infoSubTypeValue) => Config.InfoSubType.TryGetValue((infoSubType, infoSubTypeValue), out string? iconKey) ? iconKey : "Unknown";

        public static Bitmap ShowMapPlayer(Bitmap newBitmap, int mapId)
        {
            if (!FileManager.PlayerList.TryGetValue(mapId, out var playerList))
                return newBitmap;

            var mapMiniData = GetMapMiniDataByMapId(mapId);
            if (mapMiniData == null)
                return newBitmap;

            (float coordXLT, float coordYLT, float coordXRB, float coordYRB) = GetCoordinateLTRB(mapId);

            int mapOnline = 0;
            using (Graphics graphics = Graphics.FromImage(newBitmap))
            {
                foreach (var playerData in playerList)
                {
                    mapOnline++;

                    if (ShowPlayer is false || playerData.CharacterName == CurrentSearchPlayer)
                        continue;

                    (double playerX, double playerY) = ConvertWorldToScreen(mapId, playerData.PositionX, playerData.PositionY, coordXLT, coordYLT, coordXRB, coordYRB);

                    int screenX = (int)Math.Round(playerX);
                    int screenY = (int)Math.Round(playerY);

                    int avatarKey = playerData.Class;
                    if (!AvatarBitmaps.ContainsKey(avatarKey))
                        continue;

                    Bitmap avatarBitmap = AvatarBitmaps[avatarKey];
                    int avatarWidth = avatarBitmap.Width;
                    int avatarHeight = avatarBitmap.Height;

                    lock (avatarBitmap)
                        graphics.DrawImage(avatarBitmap, new Rectangle(screenX - avatarWidth / 2, screenY - avatarHeight / 2, avatarWidth, avatarHeight));

                    DrawTextWithOutline(graphics, playerData.CharacterName, (7, 9), screenX, screenY, avatarHeight, false, false);
                    DrawTextWithOutline(graphics, $"[{GetFormatString(FileManager.GetStringMessageById(1001052), playerData.Lev)}]", (6, 8), screenX, screenY, avatarHeight, false, true);
                }

                var currentPlayerData = playerList.FirstOrDefault(p => p.CharacterName == CurrentSearchPlayer);
                if (currentPlayerData != null)
                {
                    (double playerX, double playerY) = ConvertWorldToScreen(mapId, currentPlayerData.PositionX, currentPlayerData.PositionY, coordXLT, coordYLT, coordXRB, coordYRB);

                    int screenX = (int)Math.Round(playerX);
                    int screenY = (int)Math.Round(playerY);

                    int avatarKey = currentPlayerData.Class + 10;
                    if (AvatarBitmaps.ContainsKey(avatarKey))
                    {
                        Bitmap avatarBitmap = AvatarBitmaps[avatarKey];
                        int avatarWidth = avatarBitmap.Width;
                        int avatarHeight = avatarBitmap.Height;

                        lock (avatarBitmap)
                            graphics.DrawImage(avatarBitmap, new Rectangle(screenX - avatarWidth / 2, screenY - avatarHeight / 2, avatarWidth, avatarHeight));

                        DrawTextWithOutline(graphics, currentPlayerData.CharacterName, (8, 10), screenX, screenY, avatarHeight, true, false);
                        DrawTextWithOutline(graphics, $"[{GetFormatString(FileManager.GetStringMessageById(1001052), currentPlayerData.Lev)}]", (7, 9), screenX, screenY, avatarHeight, true, true);
                    }
                }
            }

            UpdatePlayerPanel(mapOnlineLabel, 3, mapOnline);
            return CombineBitmaps(newBitmap, newBitmap);
        }

        private static void DrawTextWithOutline(Graphics graphics, string? text, (int, int) fontSize, float screenX, float screenY, int avatarHeight, bool needBgText, bool isTitle)
        {
            if (text is null)
                return;

            (int fontSizeA, int fontSizeB) = fontSize;
            Font font = ImageProcessor.GetFont("Arial", fontSizeA);
            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                font = ImageProcessor.GetFont("Microsoft YaHei", fontSizeB);

            Color textColor = Color.White;

            using (Brush textBrush = new SolidBrush(textColor))
            using (Brush outlineBrush = new SolidBrush(Color.Black))
            {
                SizeF textSize = graphics.MeasureString(text, font);
                float textX = screenX - textSize.Width / 2;
                float textY = isTitle ? screenY - avatarHeight / 2 - textSize.Height : screenY + avatarHeight / 2;

                if (needBgText && Bitmap_BgText != null)
                {
                    int bgTextWidth = (int)textSize.Width + 50;
                    int bgTextHeight = (int)textSize.Height + 5;
                    int bgTextX = (int)(textX - (bgTextWidth - textSize.Width) / 2);
                    int bgTextY = (int)(textY - 2);

                    graphics.DrawImage(Bitmap_BgText, new Rectangle(bgTextX, bgTextY, bgTextWidth, bgTextHeight));
                }

                DrawOutlinedText(graphics, text, font, textBrush, outlineBrush, textX, textY);
            }
        }

        private static void DrawOutlinedText(Graphics graphics, string? text, Font font, Brush textBrush, Brush outlineBrush, float x, float y)
        {
            if (text is null)
                return;

            graphics.DrawString(text, font, outlineBrush, x - 1, y - 1);
            graphics.DrawString(text, font, outlineBrush, x + 1, y - 1);
            graphics.DrawString(text, font, outlineBrush, x - 1, y + 1);
            graphics.DrawString(text, font, outlineBrush, x + 1, y + 1);

            graphics.DrawString(text, font, textBrush, x, y);
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

        private static (float coordXLT, float coordYLT, float coordXRB, float coordYRB) GetCoordinateLTRB(int mapId)
        {
            float coordXLT = 0, coordYLT = 0;
            float coordXRB = 0, coordYRB = 0;

            var mapMiniData = GetMapMiniDataByMapId(mapId);
            if (mapMiniData != null)
            {
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
            }

            return (coordXLT, coordYLT, coordXRB, coordYRB);
        }

        public static void LoadMapData()
        {
            if (mapSearchComboBox == null)
                return;

            if (mapSearchComboBox.InvokeRequired)
            {
                mapSearchComboBox.Invoke(new Action(LoadMapData));
            }
            else
            {
                UpdateCachedSpecialMaps();
                mapSearchComboBox.Items.Clear();
                mapSearchComboBox.Items.AddRange(SpecialMap.ToArray());
                mapSearchComboBox.SelectedIndex = -1;
            }
        }

        private static void mapSearchComboBox_TextChanged(object? sender, EventArgs e)
        {
            if (mapSearchComboBox == null || mapSearchComboBox.SelectedIndex != -1)
                return;

            string txt = mapSearchComboBox.Text.ToLower();

            if (mapSearchComboBox.InvokeRequired)
            {
                mapSearchComboBox.Invoke(new Action(() => mapSearchComboBox_TextChanged(sender, e)));
            }
            else
            {
                var matches = SpecialMap
                    .Where(map => map.MapId.ToString().Contains(txt) || map.MapName.ToLower().Contains(txt))
                    .ToList();

                mapSearchComboBox.BeginUpdate();
                mapSearchComboBox.Items.Clear();

                if (matches.Count > 0)
                {
                    mapSearchComboBox.Items.AddRange(matches.ToArray());
                    mapSearchComboBox.DroppedDown = true;
                    mapSearchComboBox.Text = txt;
                    mapSearchComboBox.SelectionStart = txt.Length;
                    Control? control = sender as Control;
                    if (control != null)
                        control.Cursor = Cursors.Default;
                }
                else
                {
                    mapSearchComboBox.DroppedDown = false;
                }

                mapSearchComboBox.Select(txt.Length, 0);
                mapSearchComboBox.EndUpdate();
            }
        }

        private static void mapSearchComboBox_SelectedIndexChanged(object? sender, EventArgs e)
        {
            if (mapSearchComboBox == null || mapSearchComboBox.SelectedIndex == -1 || mapSearchComboBox.SelectedItem == null)
                return;

            if (mapSearchComboBox.InvokeRequired)
            {
                mapSearchComboBox.Invoke(new Action(() => mapSearchComboBox_SelectedIndexChanged(sender, e)));
            }
            else
            {
                mapSearchComboBox.DroppedDown = false;
            }
        }

        private static void EnterMap(ComboBox mapSearchComboBox)
        {
            if (mapSearchComboBox == null)
                return;

            SpecialMapData? selectedMap = mapSearchComboBox.SelectedItem as SpecialMapData;

            if (selectedMap == null)
            {
                string mapName = mapSearchComboBox.Text;
                selectedMap = SpecialMap.FirstOrDefault(map => map.MapName.Equals(mapName, StringComparison.OrdinalIgnoreCase));
            }

            if (selectedMap != null)
            {
                SwitchMap(selectedMap.MapId);
                Log($"Map found: [MapId: {selectedMap.MapId} MapName: {selectedMap.MapName}]");
                return;
            }

            Log($"Map {mapSearchComboBox.Text} not found.");
        }

        private static void UpdateCachedSpecialMaps()
        {
            SpecialMap.Clear();

            foreach (var mapData in FileManager.SpecialMapList.Values)
            {
                SpecialMap.Add(new SpecialMapData
                {
                    MapId = mapData.StageID,
                    MapName = FileManager.GetStringTemplateById(mapData.StageNameSID)
                });
            }
        }

        public static void LoadPlayerData()
        {
            if (playerSearchComboBox == null)
                return;

            if (playerSearchComboBox.InvokeRequired)
            {
                playerSearchComboBox.Invoke(new Action(LoadPlayerData));
            }
            else
            {
                playerSearchComboBox.Items.Clear();
                foreach (var playerList in FileManager.PlayerList.Values)
                {
                    foreach (var playerData in playerList)
                        playerSearchComboBox.Items.Add(playerData);
                }
                playerSearchComboBox.SelectedIndex = -1;
            }
        }

        private static void playerSearchComboBox_TextChanged(object? sender, EventArgs e)
        {
            if (playerSearchComboBox == null || playerSearchComboBox.SelectedIndex != -1)
                return;

            string txt = playerSearchComboBox.Text.ToLower();
            CurrentSearchPlayer = string.Empty;

            if (playerSearchComboBox.InvokeRequired)
            {
                playerSearchComboBox.Invoke(new Action(() => playerSearchComboBox_TextChanged(sender, e)));
            }
            else
            {
                var matches = FileManager.PlayerList.Values
                    .SelectMany(playerList => playerList)
                    .Where(playerData => playerData.ToString().ToLower().Contains(txt))
                    .ToList();

                playerSearchComboBox.BeginUpdate();
                playerSearchComboBox.Items.Clear();

                if (matches.Count > 0)
                {
                    foreach (var player in matches)
                        playerSearchComboBox.Items.Add(player);

                    playerSearchComboBox.DroppedDown = true;
                    playerSearchComboBox.Text = txt;
                    playerSearchComboBox.SelectionStart = txt.Length;
                    Control? control = sender as Control;
                    if (control != null)
                        control.Cursor = Cursors.Default;
                }
                else
                {
                    playerSearchComboBox.DroppedDown = false;
                }

                playerSearchComboBox.Select(txt.Length, 0);
                playerSearchComboBox.EndUpdate();
            }
        }

        private static void playerSearchComboBox_SelectedIndexChanged(object? sender, EventArgs e)
        {
            if (playerSearchComboBox == null || playerSearchComboBox.SelectedIndex == -1 || playerSearchComboBox.SelectedItem == null)
                return;

            if (playerSearchComboBox.InvokeRequired)
            {
                playerSearchComboBox.Invoke(new Action(() => playerSearchComboBox_SelectedIndexChanged(sender, e)));
            }
            else
            {
                playerSearchComboBox.Text = playerSearchComboBox.SelectedItem.ToString();
                playerSearchComboBox.DroppedDown = false;
            }
        }

        private static void SearchPlayer(ComboBox playerSearchComboBox)
        {
            string selectedPlayer = playerSearchComboBox.Text;
            if (string.IsNullOrEmpty(selectedPlayer)) return;

            foreach (var playerList in FileManager.PlayerList.Values)
            {
                var foundPlayer = playerList.FirstOrDefault(playerData =>
                    playerData.ToString().Equals(selectedPlayer, StringComparison.OrdinalIgnoreCase));

                if (foundPlayer != null)
                {
                    CurrentSearchPlayer = foundPlayer.CharacterName;
                    SwitchMap(foundPlayer.StageIdx);
                    Log($"Player found: [UID: {foundPlayer.CharacterUID}, Name: {foundPlayer.CharacterName}, MapId: {foundPlayer.StageIdx}, Position_X: {foundPlayer.PositionX:F0}, Position_Y: {foundPlayer.PositionY:F0}]");
                    return;
                }
            }

            Log($"Player {selectedPlayer} not found.");
        }

        private static string GetPlayerPanelDesc(int type = 0, int count = 0)
        {
            return type switch
            {
                1 => $"{FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 2618029, 1086612)}: {count}",
                2 => $"{FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 2618029, 1061060, 1086612)}: {count}",
                3 => $"{FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1019001, 1061060, 1086612)}: {count}",
                _ => string.Empty
            };
        }

        public static string GetFormatString(string format, params object[] args)
        {
            for (int i = 0; i < args.Length; i++)
            {
                string placeholder = "{" + (i + 1) + "}";
                format = format.Replace(placeholder, args[i]?.ToString() ?? string.Empty);
            }

            return format;
        }

        public static void UpdatePlayerPanel(Label label, int type, int count)
        {
            if (label == null)
                return;

            if (label.InvokeRequired)
            {
                label.Invoke(new Action(() =>
                {
                    label.Text = GetPlayerPanelDesc(type, count);
                }));
            }
            else
            {
                label.Text = GetPlayerPanelDesc(type, count);
            }
        }

        private static void WorldMapBox_MouseEnter(object? sender, EventArgs e)
        {
            Control? control = sender as Control;
            if (control != null)
                control.Cursor = Config.customCursor ?? Cursors.Default;
        }

        private static void WorldMapBox_MouseLeave(object? sender, EventArgs e)
        {
            Control? control = sender as Control;
            if (control != null)
                control.Cursor = Cursors.Default;
        }

        private static string GetRefreshPlayerDataButtonText() => $"{FileManager.CombineStringsWithSpaces(FileManager.GetStringMessageById, 1041024, 1041302)} ({GetFormatString(FileManager.GetStringMessageById(1026084), countdownTime)})";

        private static void RefreshPlayerData()
        {
            FileManager.LoadPlayerData();
            ResetCountdown();
        }

        private static void AutoRefreshTick()
        {
            countdownTime--;
            refreshPlayerDataButton.Text = GetRefreshPlayerDataButtonText();

            if (countdownTime <= 0)
            {
                RefreshPlayerData();
            }
        }

        private static void ResetCountdown()
        {
            countdownTime = autoRefreshInterval / 1000;
            refreshPlayerDataButton.Text = GetRefreshPlayerDataButtonText();
        }

        private static void ToggleShowPlayer(bool isChecked)
        {
            ShowPlayer = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowMerchantNpc(bool isChecked)
        {
            ShowMerchantNpc = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowResidentNpc(bool isChecked)
        {
            ShowResidentNpc = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowWayPoint(bool isChecked)
        {
            ShowWayPoint = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowGathering(bool isChecked)
        {
            ShowGathering = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowMonster(bool isChecked)
        {
            ShowMonster = isChecked;
            SwitchMap(curMapId);
        }

        private static void ToggleShowDemonSpawnPoint(bool isChecked)
        {
            ShowDemonSpawnPoint = isChecked;
            SwitchMap(curMapId);
        }
    }
}
