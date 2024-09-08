using System.Reflection;

namespace Server_Console.Database_Tool
{
    public class ItemPage : TabPage
    {
        private static void Log(string message) => DatabaseTool.Log(message);

        public static ComboBox searchBox = new ComboBox();
        private Button searchButton = new Button();
        private PictureBox iconPictureBox = new PictureBox();
        private TextBox resultTextBox = new TextBox();
        private GroupBox searchGroupBox = new GroupBox();
        private GroupBox iconGroupBox = new GroupBox();
        private GroupBox resultGroupBox = new GroupBox();

        public ItemPage()
        {
            LoadData();
        }

        private void LoadData()
        {
            InitializeComponent();
            InitializeControls();
            InitializeFormTitle();
        }

        private void InitializeComponent()
        {
            // PictureBox
            ((System.ComponentModel.ISupportInitialize)iconPictureBox).BeginInit();
            searchGroupBox.SuspendLayout();
            iconGroupBox.SuspendLayout();
            resultGroupBox.SuspendLayout();
            SuspendLayout();

            ConfigureSearchGroupBox();
            ConfigureIconGroupBox();
            ConfigureResultGroupBox();

            Controls.Add(resultGroupBox);
            Controls.Add(iconGroupBox);
            Controls.Add(searchGroupBox);
            Font = new Font("Segoe UI", 9F);

            ((System.ComponentModel.ISupportInitialize)iconPictureBox).EndInit();
            searchGroupBox.ResumeLayout(false);
            searchGroupBox.PerformLayout();
            iconGroupBox.ResumeLayout(false);
            resultGroupBox.ResumeLayout(false);
            resultGroupBox.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }

        private void ConfigureSearchGroupBox()
        {
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
        }

        private void ConfigureIconGroupBox()
        {
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
        }

        private void ConfigureResultGroupBox()
        {
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
        }

        private async void InitializeControls()
        {
            await Task.Delay(1000);
            InitializeComponent();
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

        public static void LoadComboBox()
        {
            var matchingItems = FileManager.GetMatchingItems("");

            if (searchBox.InvokeRequired)
            {
                searchBox.Invoke(new Action(() =>
                {
                    UpdateComboBox(matchingItems);
                }));
            }
            else
            {
                UpdateComboBox(matchingItems);
            }
        }

        private static void UpdateComboBox(List<string> matchingItems)
        {
            searchBox.BeginUpdate();
            searchBox.Items.Clear();

            var sortedItems = matchingItems.OrderBy(item => item).ToList();
            searchBox.Items.AddRange(sortedItems.ToArray());

            searchBox.SelectedIndex = -1;
            searchBox.EndUpdate();
        }

        private void DisplayItemData(ItemData itemData)
        {
            resultTextBox.Clear();

            resultTextBox.AppendText($"Name : {FileManager.StringTemplateMap[itemData.NameSid]?.Text ?? "N/A"}\r\n");
            resultTextBox.AppendText($"Note : {FileManager.StringTemplateMap[itemData.NoteSid]?.Text ?? "N/A"}\r\n");

            foreach (var property in typeof(ItemData).GetProperties(BindingFlags.Public | BindingFlags.Instance))
            {
                if (property.Name == "NameSid" || property.Name == "NoteSid") continue;

                var value = property.GetValue(itemData);
                resultTextBox.AppendText($"{property.Name} : {value}\r\n");
            }

            ImageProcessor.SetIconInPictureBox(itemData.Icon, itemData.Grade, itemData.Tier, itemData.TradeType, iconPictureBox);

            resultTextBox.SelectionStart = 0;
            resultTextBox.ScrollToCaret();
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
                    Log($"Item found: {itemData.ItemID}");
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
    }
}
