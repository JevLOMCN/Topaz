using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Reflection;
using System.Windows.Forms;
using System.Xml;

namespace Server_Console.Database_Tool
{
    public static class ItemPage
    {
        private static Guid _instanceId;
        private static void Log(string message) => DatabaseTool.Log(_instanceId, message);
        private static string CurrentSearchItem = string.Empty;

        public static TextBox? itemSearchTextBox;
        private static ComboBox? tierComboBox;
        private static ComboBox? classComboBox;
        private static ComboBox? tradeComboBox;
        private static GroupBox? searchGroupBox;
        private static FlowLayoutPanel? iconFlowPanel;
        private static FlowLayoutPanel? paginationPanel;
        private static Button previousButton;
        private static Button nextButton;

        public static Dictionary<int, Bitmap> CachedItemIcons = new Dictionary<int, Bitmap>();
        private const int MaxPageDisplay = 6;
        private const int MaxIconsPerPage = 120;
        private static int currentPage = 0;
        private static int totalPages = 0;

        private static Dictionary<string, int> ClassMapping;
        private static Dictionary<string, int> GradeMapping;
        private static Dictionary<string, int> TradeTypeMapping;

        private static System.Windows.Forms.Timer searchTimer = new System.Windows.Forms.Timer { Interval = 500 };
        private static bool isSearchPending = false;

        public static void Initialize(Guid instanceId, Control container)
        {
            _instanceId = instanceId;
            InitializeOptionMapping();
            var tabPage = new Panel
            {
                Name = "Item Page",
                Padding = new Padding(3),
                Size = new Size(1400, 600),
                TabIndex = 1,
                Location = new Point(4, 0),
                BackColor = Color.White
            };

            InitializeComponent(tabPage);
            container.Controls.Add(tabPage);

            searchTimer.Tick += SearchTimer_Tick;
        }

        private static void InitializeOptionMapping()
        {
            string allText = FileManager.GetStringTemplateById(5200013);

            GradeMapping = new Dictionary<string, int> { { allText, -1 } };
            for (int i = 5; i >= 1; i--)
                GradeMapping[FileManager.GetStringTemplateById(5200000 + i)] = i;

            TradeTypeMapping = new Dictionary<string, int> { { allText, -1 } };
            TradeTypeMapping[FileManager.GetStringMessageById(2004803)] = 1;
            TradeTypeMapping[FileManager.GetStringMessageById(1005966)] = 0;

            ClassMapping = new Dictionary<string, int> { { allText, -1 } };
            foreach (var avatar in Config.avatarPaths)
            {
                string className = FileManager.GetStringTemplateById(avatar.Value.StringId);
                if (!string.IsNullOrEmpty(className) && !ClassMapping.ContainsKey(className))
                {
                    ClassMapping.Add(className, avatar.Key);
                }
            }
        }

        private static void InitializeComponent(Panel tabPage)
        {
            int mainWindowWidth = tabPage.Width;

            searchGroupBox = new GroupBox
            {
                Text = FileManager.GetStringTemplateById(5100001),
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                BackColor = Color.White,
                Padding = new Padding(10),
                Dock = DockStyle.Top,
                Width = mainWindowWidth,
                Height = 100
            };

            int padding = mainWindowWidth / 70;
            iconFlowPanel = new FlowLayoutPanel
            {
                Dock = DockStyle.Fill,
                AutoScroll = true,
                WrapContents = true,
                Padding = new Padding(padding, padding, padding, padding),
                BackColor = Color.White,
                FlowDirection = FlowDirection.LeftToRight,
            };

            paginationPanel = new FlowLayoutPanel
            {
                Dock = DockStyle.Bottom,
                FlowDirection = FlowDirection.LeftToRight,
                AutoSize = true,
                Padding = new Padding(10)
            };

            InitializeSearchGroupBox(mainWindowWidth);
            tabPage.Controls.Add(iconFlowPanel);
            tabPage.Controls.Add(searchGroupBox);
            tabPage.Controls.Add(paginationPanel);
        }

        private static void InitializeSearchGroupBox(int mainWindowWidth)
        {
            int startX = 20;
            int startY = 30;
            int padding = 5;
            int labelComboSpacing = 2;
            int controlSpacing = 10;

            string searchLabelText = $"{FileManager.GetStringMessageById(1063853)}:";
            Label searchLabel = new Label
            {
                Text = searchLabelText,
                Location = new Point(startX, startY),
                Width = TextRenderer.MeasureText(searchLabelText, new Font("Segoe UI", 9F)).Width
            };

            itemSearchTextBox = new TextBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(startX + searchLabel.Width + labelComboSpacing, startY - padding / 2),
                Width = Math.Max(0, mainWindowWidth - startX * 2 - searchLabel.Width - labelComboSpacing),
                Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right,
                Text = string.Empty
            };

            itemSearchTextBox.TextChanged += (s, e) =>
            {
                currentPage = 0;
                isSearchPending = true;
                searchTimer.Stop();
                searchTimer.Start();
            };

            startY += itemSearchTextBox.Height + padding;

            string tierLabelText = $"{FileManager.GetStringMessageById(1099065)}:";
            Label tierLabel = new Label
            {
                Text = tierLabelText,
                Location = new Point(startX, startY),
                Width = TextRenderer.MeasureText(tierLabelText, new Font("Segoe UI", 9F)).Width
            };

            tierComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(startX + tierLabel.Width + labelComboSpacing, startY - padding / 2),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            foreach (var tier in GradeMapping.Keys)
            {
                tierComboBox.Items.Add(tier);
            }
            tierComboBox.SelectedIndex = 0;
            tierComboBox.SelectedIndexChanged += (s, e) =>
            {
                currentPage = 0;
                UpdateIcons();
            };

            int classStartX = startX + tierLabel.Width + labelComboSpacing + tierComboBox.Width + controlSpacing;

            string classLabelText = $"{FileManager.GetStringMessageById(1102030)}:";
            Label classLabel = new Label
            {
                Text = classLabelText,
                Location = new Point(classStartX, startY),
                Width = TextRenderer.MeasureText(classLabelText, new Font("Segoe UI", 9F)).Width
            };

            classComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(classStartX + classLabel.Width + labelComboSpacing, startY - padding / 2),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };
            foreach (var cls in ClassMapping.Keys)
            {
                classComboBox.Items.Add(cls);
            }
            classComboBox.SelectedIndex = 0;
            classComboBox.SelectedIndexChanged += (s, e) =>
            {
                currentPage = 0;
                UpdateIcons();
            };

            int tradeStartX = classStartX + classLabel.Width + labelComboSpacing + classComboBox.Width + controlSpacing;

            string tradeLabelText = $"{FileManager.GetStringMessageById(1012041)}:";
            Label tradeLabel = new Label
            {
                Text = tradeLabelText,
                Location = new Point(tradeStartX, startY),
                Width = TextRenderer.MeasureText(tradeLabelText, new Font("Segoe UI", 9F)).Width
            };

            tradeComboBox = new ComboBox
            {
                Font = new Font("Segoe UI", 9F),
                ForeColor = Color.Black,
                Location = new Point(tradeStartX + tradeLabel.Width + labelComboSpacing, startY - padding / 2),
                Width = 200,
                DropDownStyle = ComboBoxStyle.DropDownList,
                Anchor = AnchorStyles.Top | AnchorStyles.Left
            };

            foreach (var trade in TradeTypeMapping.Keys)
                tradeComboBox.Items.Add(trade);

            tradeComboBox.SelectedIndex = 0;
            tradeComboBox.SelectedIndexChanged += (s, e) =>
            {
                currentPage = 0;
                UpdateIcons();
            };

            searchGroupBox.Controls.Add(searchLabel);
            searchGroupBox.Controls.Add(itemSearchTextBox);
            searchGroupBox.Controls.Add(tierLabel);
            searchGroupBox.Controls.Add(tierComboBox);
            searchGroupBox.Controls.Add(classLabel);
            searchGroupBox.Controls.Add(classComboBox);
            searchGroupBox.Controls.Add(tradeLabel);
            searchGroupBox.Controls.Add(tradeComboBox);
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

        private static void ChangePage(int direction, int totalFilteredItems)
        {
            currentPage += direction;

            if (currentPage < 0)
                currentPage = 0;
            else if (currentPage >= totalPages)
                currentPage = totalPages - 1;

            UpdatePageButtons(totalFilteredItems);
            UpdateIcons();
        }

        private static void UpdatePageButtons(int totalFilteredItems)
        {
            if (paginationPanel.InvokeRequired)
            {
                paginationPanel.Invoke(new Action<int>(UpdatePageButtons), totalFilteredItems);
                return;
            }

            totalPages = (int)Math.Ceiling((double)totalFilteredItems / MaxIconsPerPage);
            paginationPanel.Controls.Clear();

            int buttonWidth = 100;
            int totalButtons = Math.Min(totalPages, MaxPageDisplay);
            int additionalButtons = 0;

            if (totalPages > MaxPageDisplay)
                additionalButtons = 2;

            if (currentPage == 0)
                additionalButtons--;

            if (currentPage == totalPages - 1)
                additionalButtons--;

            int buttonsTotalWidth = (totalButtons + additionalButtons) * buttonWidth;
            int startX = (paginationPanel.Width - buttonsTotalWidth) / 2;

            paginationPanel.Padding = new Padding(Math.Max(startX, 0), 0, 0, 0);

            if (currentPage > 0)
            {
                previousButton = new Button
                {
                    Text = FileManager.GetStringMessageById(1058037),
                    AutoSize = true
                };
                previousButton.Click += (s, e) => ChangePage(-1, totalFilteredItems);
                paginationPanel.Controls.Add(previousButton);
            }

            int startPage, endPage;

            if (totalPages <= MaxPageDisplay)
            {
                startPage = 0;
                endPage = totalPages - 1;
            }
            else
            {
                if (currentPage < 3)
                {
                    startPage = 0;
                    endPage = MaxPageDisplay - 1;
                }
                else if (currentPage >= totalPages - 3)
                {
                    startPage = totalPages - MaxPageDisplay;
                    endPage = totalPages - 1;
                }
                else
                {
                    startPage = currentPage - 2;
                    endPage = currentPage + 2;
                }

                if (startPage > 0)
                {
                    AddPageButton(0, totalFilteredItems);
                    if (startPage > 1)
                    {
                        paginationPanel.Controls.Add(new Label { Text = "...", AutoSize = true });
                    }
                }
            }

            for (int i = startPage; i <= endPage; i++)
                AddPageButton(i, totalFilteredItems);

            if (endPage < totalPages - 1)
            {
                if (endPage < totalPages - 2)
                    paginationPanel.Controls.Add(new Label { Text = "...", AutoSize = true });

                AddPageButton(totalPages - 1, totalFilteredItems);
            }

            if (currentPage < totalPages - 1)
            {
                nextButton = new Button
                {
                    Text = FileManager.GetStringMessageById(1058038),
                    AutoSize = true
                };
                nextButton.Click += (s, e) => ChangePage(1, totalFilteredItems);
                paginationPanel.Controls.Add(nextButton);
            }
        }

        private static void AddPageButton(int pageIndex, int totalFilteredItems)
        {
            Button pageButton = new Button
            {
                Text = (pageIndex + 1).ToString(),
                Tag = pageIndex,
                Enabled = pageIndex != currentPage,
                AutoSize = true
            };

            pageButton.Click += (s, e) =>
            {
                currentPage = (int)((Button)s).Tag;
                UpdatePageButtons(totalFilteredItems);
                UpdateIcons();
            };

            paginationPanel.Controls.Add(pageButton);
        }

        public static async Task LoadData()
        {
            if (Config.IsNewVersionDetected("Assets/Json/ITEM.json", "ItemHash", Config.iconCacheFileName))
            {
                Log("Loading icon assets ..");
                await LoadAndSaveAssets();
                Config.SaveCacheData(Config.iconCacheFileName, CachedItemIcons);
            }
            else
            {
                Log("Loading icon cached assets ..");
                Config.LoadCacheData(Config.iconCacheFileName);
            }

            var matchingItems = FileManager.ItemMap.Values.ToList();
            UpdateIcons();
            UpdatePageButtons(matchingItems.Count);
            Log("Loaded icon cached data.");
        }

        public static async Task LoadAndSaveAssets()
        {
            var tasks = FileManager.ItemMap.Values.Select(item => Task.Run(() =>
            {
                Bitmap? iconBitmap = ImageProcessor.GetIcon(item.Icon, item.Grade, item.Tier, item.TradeType);
                if (iconBitmap != null)
                {
                    lock (CachedItemIcons)
                        CachedItemIcons[item.ItemID] = iconBitmap;
                }
            })).ToList();

            await Task.WhenAll(tasks);

            int failedCount = FileManager.ItemMap.Count - CachedItemIcons.Count;
            Log($"Loaded item icons. [Success: {CachedItemIcons.Count} Failed: {failedCount}]");
        }

        private static async Task DisplaySearchResultsAsync(List<ItemData> items)
        {
            if (iconFlowPanel == null) return;

            iconFlowPanel.Controls.Clear();

            var pagedItems = items.Skip(currentPage * MaxIconsPerPage).Take(MaxIconsPerPage).ToList();

            var tasks = pagedItems.Select(item => Task.Run(() =>
            {
                if (item == null || !CachedItemIcons.ContainsKey(item.ItemID))
                    return null;

                var pb = new PictureBox
                {
                    Size = new Size(100, 100),
                    Margin = new Padding(3),
                    SizeMode = PictureBoxSizeMode.Zoom,
                    Cursor = Cursors.Hand,
                    Tag = item
                };

                pb.Image = CachedItemIcons[item.ItemID];
                pb.Click += IconPictureBox_Click;

                return pb;
            }));

            var pictureBoxes = await Task.WhenAll(tasks);
            iconFlowPanel.Controls.AddRange(pictureBoxes.Where(pb => pb != null).ToArray());
        }

        private static void IconPictureBox_Click(object sender, EventArgs e)
        {
            if (sender is PictureBox pb && pb.Tag is ItemData itemData)
            {
                ItemDetailForm.ShowItemDetails(itemData);
            }
        }

        public static void LoadItemData()
        {
            if (itemSearchTextBox == null)
                return;

            if (itemSearchTextBox.InvokeRequired)
            {
                itemSearchTextBox.Invoke(new Action(LoadItemData));
            }
            else
            {
                itemSearchTextBox.Text = string.Empty;
                itemSearchTextBox.Focus();
            }
        }

        private static void SearchTimer_Tick(object sender, EventArgs e)
        {
            searchTimer.Stop();
            if (isSearchPending)
            {
                UpdateIcons();
                isSearchPending = false;
            }
        }

        private static void UpdateIcons()
        {
            if (itemSearchTextBox == null || tierComboBox == null || classComboBox == null || tradeComboBox == null)
                return;

            if (itemSearchTextBox.InvokeRequired)
            {
                itemSearchTextBox.Invoke(new Action(UpdateIcons));
                return;
            }

            string searchTerm = itemSearchTextBox?.Text ?? string.Empty;
            var selectedGrade = GradeMapping[tierComboBox.SelectedItem.ToString()];
            var selectedClass = ClassMapping[classComboBox.SelectedItem.ToString()];
            var selectedTrade = TradeTypeMapping[tradeComboBox.SelectedItem.ToString()];

            var matchingItems = FileManager.ItemMap.Values
                .Where(itemData =>
                    itemData.ToString().Contains(searchTerm, StringComparison.OrdinalIgnoreCase) &&
                    (selectedGrade == -1 || itemData.Grade == selectedGrade) &&
                    (selectedClass == -1 || itemData.ClassId == selectedClass) &&
                    (selectedTrade == -1 || itemData.TradeType == selectedTrade))
                .ToList();

            int totalFilteredItems = matchingItems.Count;
            UpdatePageButtons(totalFilteredItems);
            DisplaySearchResultsAsync(matchingItems);
        }
    }
}
