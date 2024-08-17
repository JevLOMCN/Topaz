using System;
using System.Collections.Generic;
using System.Drawing;
using System.Linq;
using System.Reflection;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.Database_Tool
{
    public class ItemPage : TabPage
    {
        private MenuStrip menuStrip = new MenuStrip();
        private ToolStripMenuItem languageMenuItem = new ToolStripMenuItem();

        private ComboBox searchBox = new ComboBox();
        private Button searchButton = new Button();
        private PictureBox iconPictureBox = new PictureBox();
        private TextBox logTextBox = new TextBox();
        private TextBox resultTextBox = new TextBox();
        private ProgressBar progressBar = new ProgressBar();
        private GroupBox searchGroupBox = new GroupBox();
        private GroupBox iconGroupBox = new GroupBox();
        private GroupBox resultGroupBox = new GroupBox();
        private GroupBox logGroupBox = new GroupBox();

        public delegate void LogDelegate(string message);
        public LogDelegate? LogMethod;

        public ItemPage()
        {
            this.Text = "Items";
            this.UseVisualStyleBackColor = true;
            LoadData();
        }

        private void LoadData()
        {
            Config.Initialize();
            InitializeComponent();
            // InitializeFormTitle();
            InitializeLanguageMenu();
            DelayAndLoadData();
            IconExtractor.InitZlibSync();
            IconExtractor.InitializeProvider();

        }

        private void InitializeLanguageMenu()
        {
            languageMenuItem.DropDownItems.Clear();
            var languages = new Dictionary<string, string>
            {
                { "CHT", "中文(繁體)" },
                { "CHS", "中文(简体)" },
                { "JPN", "日本語" },
                { "ENG", "English" },
                { "THA", "ภาษาไทย" },
                { "IND", "Bahasa Indonesia" },
                { "VIE", "Tiếng Việt" },
                { "GER", "Deutsch" },
                { "SPA", "Español" },
                { "POR", "Português" },
                { "RUS", "Русский" }
            };

            foreach (var lang in languages)
            {
                var menuItem = new ToolStripMenuItem(lang.Value)
                {
                    Tag = lang.Key
                };
                menuItem.Click -= LanguageMenuItem_Click;
                menuItem.Click += LanguageMenuItem_Click;
                languageMenuItem.DropDownItems.Add(menuItem);
            }
        }

        private void InitializeComponent()
        {
            menuStrip.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)iconPictureBox).BeginInit();
            searchGroupBox.SuspendLayout();
            iconGroupBox.SuspendLayout();
            resultGroupBox.SuspendLayout();
            logGroupBox.SuspendLayout();
            this.SuspendLayout();

            // MenuStrip
            menuStrip.BackColor = Color.White;
            menuStrip.Font = new Font("Segoe UI", 9F);
            menuStrip.ForeColor = Color.Black;
            menuStrip.ImageScalingSize = new Size(24, 24);
            menuStrip.Items.AddRange(new ToolStripItem[] { languageMenuItem });
            menuStrip.Location = new Point(0, 0);
            menuStrip.Name = "menuStrip";
            menuStrip.Size = new Size(1456, 33);
            menuStrip.TabIndex = 0;
            menuStrip.Text = "menuStrip";
            menuStrip.Dock = DockStyle.Top;
            this.Controls.Add(menuStrip);

            // languageMenuItem
            languageMenuItem.Name = "languageMenuItem";
            languageMenuItem.Size = new Size(105, 29);
            languageMenuItem.Text = "Language";

            // searchGroupBox
            searchGroupBox.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            searchGroupBox.BackColor = Color.White;
            searchGroupBox.Controls.Add(searchBox);
            searchGroupBox.Controls.Add(searchButton);
            searchGroupBox.ForeColor = Color.Black;
            searchGroupBox.Dock = DockStyle.Top;
            searchGroupBox.Location = new Point(28, 55);
            searchGroupBox.Name = "searchGroupBox";
            searchGroupBox.Size = new Size(1400, 100);
            searchGroupBox.TabIndex = 0;
            searchGroupBox.TabStop = false;
            searchGroupBox.Text = "Search";

            // searchBox
            searchBox.Anchor = AnchorStyles.Top | AnchorStyles.Left | AnchorStyles.Right;
            searchBox.BackColor = Color.White;
            searchBox.ForeColor = Color.Black;
            searchBox.Location = new Point(20, 40);
            searchBox.Name = "searchBox";
            searchBox.Size = new Size(1048, 33);
            searchBox.TabIndex = 0;
            searchBox.SelectedIndexChanged += searchBox_SelectedIndexChanged;
            searchBox.TextChanged += searchBox_TextChanged;

            // searchButton
            searchButton.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            searchButton.BackColor = Color.White;
            searchButton.ForeColor = Color.Black;
            searchButton.Location = new Point(1097, 40);
            searchButton.Name = "searchButton";
            searchButton.Size = new Size(281, 32);
            searchButton.TabIndex = 1;
            searchButton.Text = "Search";
            searchButton.UseVisualStyleBackColor = false;
            searchButton.Click += SearchButton_Click;




            // iconGroupBox
            iconGroupBox.Anchor = AnchorStyles.Top | AnchorStyles.Bottom | AnchorStyles.Left;
            iconGroupBox.BackColor = Color.White;
            iconGroupBox.Controls.Add(iconPictureBox);
            iconGroupBox.ForeColor = Color.Black;
            iconGroupBox.Dock = DockStyle.Left;
            iconGroupBox.Width = 300;
            iconGroupBox.TabIndex = 1;
            iconGroupBox.TabStop = false;
            iconGroupBox.Text = "Icon";

            // iconPictureBox
            iconPictureBox.Location = new Point(20, 150);
            iconPictureBox.Name = "iconPictureBox";
            iconPictureBox.Size = new Size(256, 256);
            iconPictureBox.SizeMode = PictureBoxSizeMode.Zoom;
            iconPictureBox.TabIndex = 2;
            iconPictureBox.TabStop = false;

            // resultGroupBox
            resultGroupBox.BackColor = Color.White;
            resultGroupBox.Controls.Add(resultTextBox);
            resultGroupBox.ForeColor = Color.Black;
            resultGroupBox.Dock = DockStyle.Fill;
            resultGroupBox.TabIndex = 2;
            resultGroupBox.TabStop = false;
            resultGroupBox.Text = "Detail";

            // resultTextBox
            resultTextBox.BackColor = Color.White;
            resultTextBox.ForeColor = Color.Black;
            resultTextBox.Dock = DockStyle.Fill;
            resultTextBox.Multiline = true;
            resultTextBox.ReadOnly = true;
            resultTextBox.ScrollBars = ScrollBars.Vertical;
            resultTextBox.TabIndex = 4;

            // logGroupBox
            logGroupBox.BackColor = Color.White;
            logGroupBox.Controls.Add(logTextBox);
            logGroupBox.ForeColor = Color.Black;
            logGroupBox.Dock = DockStyle.Bottom;
            logGroupBox.Height = 200;
            logGroupBox.TabIndex = 3;
            logGroupBox.TabStop = false;
            logGroupBox.Text = "Log";

            // logTextBox
            logTextBox.BackColor = Color.White;
            logTextBox.ForeColor = Color.Black;
            logTextBox.Dock = DockStyle.Fill;
            logTextBox.Multiline = true;
            logTextBox.ReadOnly = true;
            logTextBox.ScrollBars = ScrollBars.Vertical;
            logTextBox.TabIndex = 3;

            // progressBar
            progressBar.Dock = DockStyle.Bottom;
            progressBar.TabIndex = 5;
            progressBar.Style = ProgressBarStyle.Continuous;

            // Adding all components to ItemsPage
            this.Controls.Add(resultGroupBox);
            this.Controls.Add(iconGroupBox);
            this.Controls.Add(searchGroupBox);
            this.Controls.Add(logGroupBox);
            this.Controls.Add(progressBar);
            this.Controls.Add(menuStrip);
            this.Font = new Font("Segoe UI", 9F);

            menuStrip.ResumeLayout(false);
            menuStrip.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)iconPictureBox).EndInit();
            searchGroupBox.ResumeLayout(false);
            searchGroupBox.PerformLayout();
            iconGroupBox.ResumeLayout(false);
            resultGroupBox.ResumeLayout(false);
            resultGroupBox.PerformLayout();
            logGroupBox.ResumeLayout(false);
            logGroupBox.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();
        }



        private void InitializeFormTitle()
        {
            string version = Assembly.GetExecutingAssembly()
                                  .GetCustomAttribute<AssemblyInformationalVersionAttribute>()?
                                  .InformationalVersion ?? "0.0.0";
            string author = Assembly.GetExecutingAssembly()
                                  .GetCustomAttributes<AssemblyMetadataAttribute>()
                                  .FirstOrDefault(a => a.Key == "Authors")?
                                  .Value ?? "Sumiao";
            this.Text = $"Mir4Tool v{version} [By {author}]";
        }

        private async void DelayAndLoadData()
        {
            Log("Starting data loading...");

            FileManager.SetLogMethod(Log);
            IconExtractor.SetLogMethod(Log);
            FileManager.SetProgressUpdateMethod(UpdateProgressBar);
            await Task.Run(() => FileManager.LoadData());
            InitializeComponent();
            await Task.Delay(1000);

            progressBar.Visible = false;

            LoadComboBox();
        }

        private void LoadComboBox()
        {
            var matchingItems = FileManager.GetMatchingItems("");
            searchBox.BeginUpdate();
            searchBox.Items.Clear();

            var sortedItems = matchingItems.OrderBy(item => item);
            searchBox.Items.AddRange(sortedItems.ToArray());

            searchBox.SelectedIndex = -1;
            searchBox.EndUpdate();
        }

        private void DisplayItemData(ItemData itemData)
        {
            resultTextBox.Clear();
            resultTextBox.AppendText($"Name : {FileManager.StringTemplateMap[itemData.NameSid]?.CHS ?? "N/A"}\r\n");
            resultTextBox.AppendText($"ItemId : {itemData.ItemId}\r\n");
            resultTextBox.AppendText($"UseId : {itemData.UseId}\r\n");
            resultTextBox.AppendText($"Note : {FileManager.StringTemplateMap[itemData.NoteSid]?.CHS ?? "N/A"}\r\n");
            resultTextBox.AppendText($"MeshId : {itemData.MeshId}\r\n");
            resultTextBox.AppendText($"Icon : {itemData.Icon}\r\n");
            resultTextBox.AppendText($"Level : {itemData.Level}\r\n");
            resultTextBox.AppendText($"MainType : {itemData.MainType}\r\n");
            resultTextBox.AppendText($"SubType : {itemData.SubType}\r\n");
            resultTextBox.AppendText($"SmeltingType : {itemData.SmeltingType}\r\n");
            resultTextBox.AppendText($"SortOrder : {itemData.SortOrder}\r\n");
            resultTextBox.AppendText($"ClassId : {itemData.ClassId}\r\n");
            resultTextBox.AppendText($"ReqClassLevel : {itemData.ReqClassLevel}\r\n");
            resultTextBox.AppendText($"Tier : {itemData.Tier}\r\n");
            resultTextBox.AppendText($"Grade : {itemData.Grade}\r\n");
            resultTextBox.AppendText($"PET_Origin : {itemData.PET_Origin}\r\n");
            resultTextBox.AppendText($"SellType : {itemData.SellType}\r\n");
            resultTextBox.AppendText($"SellPrice : {itemData.SellPrice}\r\n");
            resultTextBox.AppendText($"Stackable : {itemData.Stackable}\r\n");
            resultTextBox.AppendText($"CoolTime : {itemData.CoolTime}\r\n");
            resultTextBox.AppendText($"BuffId : {itemData.BuffId}\r\n");
            resultTextBox.AppendText($"ItemOptionType : {itemData.ItemOptionType}\r\n");
            resultTextBox.AppendText($"SetGroupId : {itemData.SetGroupId}\r\n");
            resultTextBox.AppendText($"ReinforceMaxLv : {itemData.ReinforceMaxLv}\r\n");
            resultTextBox.AppendText($"SmeltingMaxCount : {itemData.SmeltingMaxCount}\r\n");
            resultTextBox.AppendText($"JewelUpgradeType : {itemData.JewelUpgradeType}\r\n");
            resultTextBox.AppendText($"JewelUpgradeCount : {itemData.JewelUpgradeCount}\r\n");
            resultTextBox.AppendText($"RandomGetInfoId : {itemData.RandomGetInfoId}\r\n");
            resultTextBox.AppendText($"SummonGroupId : {itemData.SummonGroupId}\r\n");
            resultTextBox.AppendText($"CastingType : {itemData.CastingType}\r\n");
            resultTextBox.AppendText($"CastingTime : {itemData.CastingTime}\r\n");
            resultTextBox.AppendText($"SummonStageType : {itemData.SummonStageType}\r\n");
            resultTextBox.AppendText($"ItemCasting : {itemData.ItemCasting}\r\n");
            resultTextBox.AppendText($"CollectAniType : {itemData.CollectAniType}\r\n");
            resultTextBox.AppendText($"GetWayId : {itemData.GetWayId}\r\n");
            resultTextBox.AppendText($"OverlapEquipAble : {itemData.OverlapEquipAble}\r\n");
            resultTextBox.AppendText($"UnsealingType : {itemData.UnsealingType}\r\n");
            resultTextBox.AppendText($"OpenboxRewardType : {itemData.OpenboxRewardType}\r\n");
            resultTextBox.AppendText($"OpenboxRewardId : {itemData.OpenboxRewardId}\r\n");
            resultTextBox.AppendText($"RandomGetCnt : {itemData.RandomGetCnt}\r\n");
            resultTextBox.AppendText($"Durability : {itemData.Durability}\r\n");
            resultTextBox.AppendText($"Durability_RepairAble : {itemData.Durability_RepairAble}\r\n");
            resultTextBox.AppendText($"TradeType : {itemData.TradeType}\r\n");
            resultTextBox.AppendText($"UseTimeType : {itemData.UseTimeType}\r\n");
            resultTextBox.AppendText($"UseEndType_Period : {itemData.UseEndType_Period}\r\n");
            resultTextBox.AppendText($"UseEndType_Minute : {itemData.UseEndType_Minute}\r\n");
            resultTextBox.AppendText($"Lockable : {itemData.Lockable}\r\n");
            resultTextBox.AppendText($"XDracoDelayMinute : {itemData.XDracoDelayMinute}\r\n");
            resultTextBox.AppendText($"GachaTap : {itemData.GachaTap}\r\n");
            resultTextBox.AppendText($"TranceGroup : {itemData.TranceGroup}\r\n");
            resultTextBox.AppendText($"EquipGroup : {itemData.EquipGroup}\r\n");

            IconExtractor.SetIconInPictureBox(itemData.Icon, itemData.Grade, iconPictureBox);

            resultTextBox.SelectionStart = 0;
            resultTextBox.ScrollToCaret();
        }

        private void Log(string message)
        {
            if (logTextBox.InvokeRequired)
            {
                logTextBox.Invoke(new Action(() => logTextBox.AppendText(message + Environment.NewLine)));
            }
            else
            {
                logTextBox.AppendText(message + Environment.NewLine);
            }
        }

        private void UpdateProgressBar(int progress)
        {
            if (progressBar.InvokeRequired)
            {
                progressBar.Invoke(new Action(() => progressBar.Value = progress));
            }
            else
            {
                progressBar.Value = progress;
            }
        }

        private string lastText = string.Empty;
        private void searchBox_TextChanged(object sender, EventArgs e)
        {
            if (searchBox.SelectedIndex == -1)
            {
                lastText = searchBox.Text;

                string txt = searchBox.Text;
                var matches = FileManager.GetMatchingItems(txt);

                searchBox.BeginUpdate();
                searchBox.Items.Clear();

                if (matches.Count > 0)
                {
                    var sortedItems = matches.OrderBy(item => item);
                    searchBox.Items.AddRange(sortedItems.ToArray());
                    searchBox.DroppedDown = true;
                    searchBox.Text = lastText;
                    searchBox.SelectionStart = searchBox.Text.Length;
                    Cursor = Cursors.Default;
                }
                else
                {
                    searchBox.DroppedDown = false;
                }

                searchBox.Select(txt.Length, 0);
                searchBox.EndUpdate();
            }
        }

        private void searchBox_DropDown(object sender, EventArgs e)
        {
            searchBox.Text = lastText;
            searchBox.SelectionStart = searchBox.Text.Length;
        }

        private void searchBox_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (searchBox.SelectedIndex != -1)
            {
                searchBox.Text = searchBox.SelectedItem.ToString();
                searchBox.DroppedDown = false;
            }
        }

        private void SearchButton_Click(object sender, EventArgs e)
        {
            string searchTerm = searchBox.Text.Trim();

            if (string.IsNullOrEmpty(searchTerm))
            {
                Log("Please enter a valid value.");
                return;
            }

            Log($"Searching: {searchTerm}");

            try
            {
                if (FileManager.CombinedIndex.TryGetValue(searchTerm, out var itemData))
                {
                    Log($"Item found: {itemData.Name}");
                    DisplayItemData(itemData);
                }
                else
                {
                    Log($"Error: The given value '{searchTerm}' was not found.");
                }
            }
            catch (Exception ex)
            {
                Log($"Error: {ex.Message}");
            }
        }

        private void iconPictureBox_Click(object sender, EventArgs e)
        {

        }

        private void LanguageMenuItem_Click(object sender, EventArgs e)
        {
            var selectedMenuItem = sender as ToolStripMenuItem;
            var languageCode = selectedMenuItem.Tag.ToString();

            if (Config.CurrentLanguage == languageCode)
                return;

            string previousLanguage = Config.CurrentLanguage;

            Config.CurrentLanguage = languageCode;
            Config.SaveLanguageSetting();

            OnChangeLanguage();
            Log($"Language changed from {previousLanguage} to {Config.CurrentLanguage}.");
        }

        private void OnChangeLanguage()
        {
            searchButton.Click -= SearchButton_Click;
            searchBox.SelectedIndexChanged -= searchBox_SelectedIndexChanged;
            searchBox.TextChanged -= searchBox_TextChanged;
            this.Controls.Clear();
            searchBox.Text = "";
            resultTextBox.Clear();
            logTextBox.Clear();
            iconPictureBox.Image = null;
            progressBar.Visible = true;
            LoadData();
        }
    }
}
