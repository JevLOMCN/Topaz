namespace Server_Console.Database
{
    partial class DatabaseForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(DatabaseForm));
            AdminTree = new TreeView();
            DatabaseTabs = new TabControl();
            tabAdmin = new TabPage();
            tableLayoutPanel2 = new TableLayoutPanel();
            AdminData = new DataGridView();
            tabDevice = new TabPage();
            tableLayoutPanel4 = new TableLayoutPanel();
            DeviceTree = new TreeView();
            DeviceData = new DataGridView();
            tabFront = new TabPage();
            tableLayoutPanel5 = new TableLayoutPanel();
            FrontTree = new TreeView();
            FrontData = new DataGridView();
            tabGame = new TabPage();
            tableLayoutPanel6 = new TableLayoutPanel();
            GameTree = new TreeView();
            GameData = new DataGridView();
            tabUser = new TabPage();
            tableLayoutPanel8 = new TableLayoutPanel();
            UserTree = new TreeView();
            UserData = new DataGridView();
            tabPlayerSearch = new TabPage();
            PlayersBox = new GroupBox();
            label1 = new Label();
            lb_IgnoredList = new ListBox();
            AccountIDLabel = new Label();
            btnFilter = new Button();
            AccountNameLabel = new Label();
            AccountNameSearch = new TextBox();
            EmailLabel = new Label();
            CharIDSearch = new TextBox();
            AccountIDSearch = new TextBox();
            CharNameLabel = new Label();
            EmailSearch = new TextBox();
            CharIDLabel = new Label();
            CharNameSearch = new TextBox();
            tableLayoutPanel1 = new TableLayoutPanel();
            label2 = new Label();
            treeView1 = new TreeView();
            dataGridView1 = new DataGridView();
            tableLayoutPanel3 = new TableLayoutPanel();
            treeView5 = new TreeView();
            dataGridView5 = new DataGridView();
            tableLayoutPanel7 = new TableLayoutPanel();
            menuStrip1 = new MenuStrip();
            tsmDB_Settings = new ToolStripMenuItem();
            tsm_KeyWarning = new ToolStripTextBox();
            tsm_TableDescription = new ToolStripTextBox();
            TableSearchBox = new TextBox();
            DatabaseTabs.SuspendLayout();
            tabAdmin.SuspendLayout();
            tableLayoutPanel2.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)AdminData).BeginInit();
            tabDevice.SuspendLayout();
            tableLayoutPanel4.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)DeviceData).BeginInit();
            tabFront.SuspendLayout();
            tableLayoutPanel5.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)FrontData).BeginInit();
            tabGame.SuspendLayout();
            tableLayoutPanel6.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)GameData).BeginInit();
            tabUser.SuspendLayout();
            tableLayoutPanel8.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)UserData).BeginInit();
            tabPlayerSearch.SuspendLayout();
            PlayersBox.SuspendLayout();
            tableLayoutPanel1.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)dataGridView1).BeginInit();
            tableLayoutPanel3.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)dataGridView5).BeginInit();
            tableLayoutPanel7.SuspendLayout();
            menuStrip1.SuspendLayout();
            SuspendLayout();
            // 
            // AdminTree
            // 
            AdminTree.Dock = DockStyle.Fill;
            AdminTree.FullRowSelect = true;
            AdminTree.Location = new Point(3, 3);
            AdminTree.Name = "AdminTree";
            AdminTree.Size = new Size(186, 606);
            AdminTree.TabIndex = 0;
            AdminTree.NodeMouseClick += AdminTree_NodeMouseClick;
            // 
            // DatabaseTabs
            // 
            DatabaseTabs.Controls.Add(tabAdmin);
            DatabaseTabs.Controls.Add(tabDevice);
            DatabaseTabs.Controls.Add(tabFront);
            DatabaseTabs.Controls.Add(tabGame);
            DatabaseTabs.Controls.Add(tabUser);
            DatabaseTabs.Controls.Add(tabPlayerSearch);
            DatabaseTabs.Dock = DockStyle.Fill;
            DatabaseTabs.Location = new Point(137, 3);
            DatabaseTabs.Name = "DatabaseTabs";
            DatabaseTabs.SelectedIndex = 0;
            DatabaseTabs.Size = new Size(1124, 646);
            DatabaseTabs.TabIndex = 2;
            DatabaseTabs.SelectedIndexChanged += DatabaseTabs_SelectedIndexChanged;
            // 
            // tabAdmin
            // 
            tabAdmin.Controls.Add(tableLayoutPanel2);
            tabAdmin.Location = new Point(4, 24);
            tabAdmin.Name = "tabAdmin";
            tabAdmin.Padding = new Padding(3);
            tabAdmin.Size = new Size(1116, 618);
            tabAdmin.TabIndex = 0;
            tabAdmin.Text = "Admin";
            tabAdmin.UseVisualStyleBackColor = true;
            // 
            // tableLayoutPanel2
            // 
            tableLayoutPanel2.ColumnCount = 2;
            tableLayoutPanel2.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel2.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel2.Controls.Add(AdminTree, 0, 0);
            tableLayoutPanel2.Controls.Add(AdminData, 1, 0);
            tableLayoutPanel2.Dock = DockStyle.Fill;
            tableLayoutPanel2.Location = new Point(3, 3);
            tableLayoutPanel2.Name = "tableLayoutPanel2";
            tableLayoutPanel2.RowCount = 1;
            tableLayoutPanel2.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel2.Size = new Size(1110, 612);
            tableLayoutPanel2.TabIndex = 2;
            // 
            // AdminData
            // 
            AdminData.BackgroundColor = SystemColors.Window;
            AdminData.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            AdminData.Dock = DockStyle.Fill;
            AdminData.GridColor = Color.Black;
            AdminData.Location = new Point(195, 3);
            AdminData.Name = "AdminData";
            AdminData.Size = new Size(912, 606);
            AdminData.TabIndex = 1;
            AdminData.CellEndEdit += AdminData_CellEndEdit;
            AdminData.CellLeave += AdminData_CellLeave;
            // 
            // tabDevice
            // 
            tabDevice.Controls.Add(tableLayoutPanel4);
            tabDevice.Location = new Point(4, 24);
            tabDevice.Name = "tabDevice";
            tabDevice.Padding = new Padding(3);
            tabDevice.Size = new Size(1116, 618);
            tabDevice.TabIndex = 2;
            tabDevice.Text = "Device";
            tabDevice.UseVisualStyleBackColor = true;
            // 
            // tableLayoutPanel4
            // 
            tableLayoutPanel4.ColumnCount = 2;
            tableLayoutPanel4.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel4.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel4.Controls.Add(DeviceTree, 0, 0);
            tableLayoutPanel4.Controls.Add(DeviceData, 1, 0);
            tableLayoutPanel4.Dock = DockStyle.Fill;
            tableLayoutPanel4.Location = new Point(3, 3);
            tableLayoutPanel4.Name = "tableLayoutPanel4";
            tableLayoutPanel4.RowCount = 1;
            tableLayoutPanel4.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel4.Size = new Size(1110, 612);
            tableLayoutPanel4.TabIndex = 3;
            // 
            // DeviceTree
            // 
            DeviceTree.Dock = DockStyle.Fill;
            DeviceTree.FullRowSelect = true;
            DeviceTree.Location = new Point(3, 3);
            DeviceTree.Name = "DeviceTree";
            DeviceTree.Size = new Size(186, 606);
            DeviceTree.TabIndex = 0;
            DeviceTree.AfterSelect += DeviceTree_AfterSelect;
            DeviceTree.NodeMouseClick += DeviceTree_NodeMouseClick;
            // 
            // DeviceData
            // 
            DeviceData.BackgroundColor = SystemColors.Window;
            DeviceData.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            DeviceData.Dock = DockStyle.Fill;
            DeviceData.GridColor = Color.Black;
            DeviceData.Location = new Point(195, 3);
            DeviceData.Name = "DeviceData";
            DeviceData.Size = new Size(912, 606);
            DeviceData.TabIndex = 1;
            DeviceData.CellEndEdit += DeviceData_CellEndEdit;
            DeviceData.CellLeave += DeviceData_CellLeave;
            // 
            // tabFront
            // 
            tabFront.Controls.Add(tableLayoutPanel5);
            tabFront.Location = new Point(4, 24);
            tabFront.Name = "tabFront";
            tabFront.Size = new Size(1116, 618);
            tabFront.TabIndex = 3;
            tabFront.Text = "Front";
            tabFront.UseVisualStyleBackColor = true;
            // 
            // tableLayoutPanel5
            // 
            tableLayoutPanel5.ColumnCount = 2;
            tableLayoutPanel5.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel5.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel5.Controls.Add(FrontTree, 0, 0);
            tableLayoutPanel5.Controls.Add(FrontData, 1, 0);
            tableLayoutPanel5.Dock = DockStyle.Fill;
            tableLayoutPanel5.Location = new Point(0, 0);
            tableLayoutPanel5.Name = "tableLayoutPanel5";
            tableLayoutPanel5.RowCount = 1;
            tableLayoutPanel5.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel5.Size = new Size(1116, 618);
            tableLayoutPanel5.TabIndex = 3;
            // 
            // FrontTree
            // 
            FrontTree.Dock = DockStyle.Fill;
            FrontTree.FullRowSelect = true;
            FrontTree.Location = new Point(3, 3);
            FrontTree.Name = "FrontTree";
            FrontTree.Size = new Size(187, 612);
            FrontTree.TabIndex = 0;
            FrontTree.NodeMouseClick += FrontTree_NodeMouseClick;
            // 
            // FrontData
            // 
            FrontData.BackgroundColor = SystemColors.Window;
            FrontData.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            FrontData.Dock = DockStyle.Fill;
            FrontData.GridColor = Color.Black;
            FrontData.Location = new Point(196, 3);
            FrontData.Name = "FrontData";
            FrontData.Size = new Size(917, 612);
            FrontData.TabIndex = 1;
            FrontData.CellEndEdit += FrontData_CellEndEdit;
            FrontData.CellLeave += FrontData_CellLeave;
            // 
            // tabGame
            // 
            tabGame.Controls.Add(tableLayoutPanel6);
            tabGame.Location = new Point(4, 24);
            tabGame.Name = "tabGame";
            tabGame.Size = new Size(1116, 618);
            tabGame.TabIndex = 4;
            tabGame.Text = "Game";
            tabGame.UseVisualStyleBackColor = true;
            // 
            // tableLayoutPanel6
            // 
            tableLayoutPanel6.ColumnCount = 2;
            tableLayoutPanel6.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel6.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel6.Controls.Add(GameTree, 0, 0);
            tableLayoutPanel6.Controls.Add(GameData, 1, 0);
            tableLayoutPanel6.Dock = DockStyle.Fill;
            tableLayoutPanel6.Location = new Point(0, 0);
            tableLayoutPanel6.Name = "tableLayoutPanel6";
            tableLayoutPanel6.RowCount = 1;
            tableLayoutPanel6.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel6.Size = new Size(1116, 618);
            tableLayoutPanel6.TabIndex = 3;
            // 
            // GameTree
            // 
            GameTree.Dock = DockStyle.Fill;
            GameTree.FullRowSelect = true;
            GameTree.Location = new Point(3, 3);
            GameTree.Name = "GameTree";
            GameTree.Size = new Size(187, 612);
            GameTree.TabIndex = 0;
            GameTree.NodeMouseClick += GameTree_NodeMouseClick;
            // 
            // GameData
            // 
            GameData.BackgroundColor = SystemColors.Window;
            GameData.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            GameData.Dock = DockStyle.Fill;
            GameData.GridColor = Color.Black;
            GameData.Location = new Point(196, 3);
            GameData.Name = "GameData";
            GameData.Size = new Size(917, 612);
            GameData.TabIndex = 1;
            GameData.CellEndEdit += GameData_CellEndEdit;
            GameData.CellLeave += GameData_CellLeave;
            // 
            // tabUser
            // 
            tabUser.Controls.Add(tableLayoutPanel8);
            tabUser.Location = new Point(4, 24);
            tabUser.Name = "tabUser";
            tabUser.Size = new Size(1116, 618);
            tabUser.TabIndex = 5;
            tabUser.Text = "User";
            tabUser.UseVisualStyleBackColor = true;
            // 
            // tableLayoutPanel8
            // 
            tableLayoutPanel8.ColumnCount = 2;
            tableLayoutPanel8.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel8.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel8.Controls.Add(UserTree, 0, 0);
            tableLayoutPanel8.Controls.Add(UserData, 1, 0);
            tableLayoutPanel8.Dock = DockStyle.Fill;
            tableLayoutPanel8.Location = new Point(0, 0);
            tableLayoutPanel8.Name = "tableLayoutPanel8";
            tableLayoutPanel8.RowCount = 1;
            tableLayoutPanel8.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel8.Size = new Size(1116, 618);
            tableLayoutPanel8.TabIndex = 3;
            // 
            // UserTree
            // 
            UserTree.Dock = DockStyle.Fill;
            UserTree.FullRowSelect = true;
            UserTree.Location = new Point(3, 3);
            UserTree.Name = "UserTree";
            UserTree.Size = new Size(187, 612);
            UserTree.TabIndex = 0;
            UserTree.NodeMouseClick += UserTree_NodeMouseClick;
            // 
            // UserData
            // 
            UserData.BackgroundColor = SystemColors.Window;
            UserData.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            UserData.Dock = DockStyle.Fill;
            UserData.GridColor = Color.Black;
            UserData.Location = new Point(196, 3);
            UserData.Name = "UserData";
            UserData.Size = new Size(917, 612);
            UserData.TabIndex = 1;
            UserData.CellEndEdit += UserData_CellEndEdit;
            UserData.CellLeave += UserData_CellLeave;
            // 
            // tabPlayerSearch
            // 
            tabPlayerSearch.Controls.Add(PlayersBox);
            tabPlayerSearch.Location = new Point(4, 24);
            tabPlayerSearch.Name = "tabPlayerSearch";
            tabPlayerSearch.Size = new Size(1116, 618);
            tabPlayerSearch.TabIndex = 6;
            tabPlayerSearch.Text = "Player Search";
            tabPlayerSearch.UseVisualStyleBackColor = true;
            // 
            // PlayersBox
            // 
            PlayersBox.Controls.Add(label1);
            PlayersBox.Controls.Add(lb_IgnoredList);
            PlayersBox.Controls.Add(AccountIDLabel);
            PlayersBox.Controls.Add(btnFilter);
            PlayersBox.Controls.Add(AccountNameLabel);
            PlayersBox.Controls.Add(AccountNameSearch);
            PlayersBox.Controls.Add(EmailLabel);
            PlayersBox.Controls.Add(CharIDSearch);
            PlayersBox.Controls.Add(AccountIDSearch);
            PlayersBox.Controls.Add(CharNameLabel);
            PlayersBox.Controls.Add(EmailSearch);
            PlayersBox.Controls.Add(CharIDLabel);
            PlayersBox.Controls.Add(CharNameSearch);
            PlayersBox.Location = new Point(3, 3);
            PlayersBox.Name = "PlayersBox";
            PlayersBox.Size = new Size(135, 273);
            PlayersBox.TabIndex = 0;
            PlayersBox.TabStop = false;
            PlayersBox.Text = "Player Lookup";
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Location = new Point(3, 347);
            label1.Name = "label1";
            label1.Size = new Size(83, 15);
            label1.TabIndex = 12;
            label1.Text = "Ignored Tables";
            // 
            // lb_IgnoredList
            // 
            lb_IgnoredList.FormattingEnabled = true;
            lb_IgnoredList.ItemHeight = 15;
            lb_IgnoredList.Location = new Point(3, 365);
            lb_IgnoredList.Name = "lb_IgnoredList";
            lb_IgnoredList.SelectionMode = SelectionMode.None;
            lb_IgnoredList.Size = new Size(120, 274);
            lb_IgnoredList.TabIndex = 11;
            // 
            // AccountIDLabel
            // 
            AccountIDLabel.AutoSize = true;
            AccountIDLabel.Location = new Point(6, 19);
            AccountIDLabel.Name = "AccountIDLabel";
            AccountIDLabel.Size = new Size(66, 15);
            AccountIDLabel.TabIndex = 2;
            AccountIDLabel.Text = "Account ID";
            // 
            // btnFilter
            // 
            btnFilter.Location = new Point(53, 242);
            btnFilter.Name = "btnFilter";
            btnFilter.Size = new Size(75, 23);
            btnFilter.TabIndex = 10;
            btnFilter.Text = "Filter";
            btnFilter.UseVisualStyleBackColor = true;
            // 
            // AccountNameLabel
            // 
            AccountNameLabel.AutoSize = true;
            AccountNameLabel.Location = new Point(6, 107);
            AccountNameLabel.Name = "AccountNameLabel";
            AccountNameLabel.Size = new Size(87, 15);
            AccountNameLabel.TabIndex = 5;
            AccountNameLabel.Text = "Account Name";
            // 
            // AccountNameSearch
            // 
            AccountNameSearch.Location = new Point(6, 125);
            AccountNameSearch.Name = "AccountNameSearch";
            AccountNameSearch.Size = new Size(122, 23);
            AccountNameSearch.TabIndex = 4;
            // 
            // EmailLabel
            // 
            EmailLabel.AutoSize = true;
            EmailLabel.Location = new Point(6, 195);
            EmailLabel.Name = "EmailLabel";
            EmailLabel.Size = new Size(36, 15);
            EmailLabel.TabIndex = 9;
            EmailLabel.Text = "Email";
            // 
            // CharIDSearch
            // 
            CharIDSearch.Location = new Point(6, 169);
            CharIDSearch.Name = "CharIDSearch";
            CharIDSearch.Size = new Size(122, 23);
            CharIDSearch.TabIndex = 6;
            // 
            // AccountIDSearch
            // 
            AccountIDSearch.Location = new Point(6, 37);
            AccountIDSearch.Name = "AccountIDSearch";
            AccountIDSearch.Size = new Size(122, 23);
            AccountIDSearch.TabIndex = 0;
            // 
            // CharNameLabel
            // 
            CharNameLabel.AutoSize = true;
            CharNameLabel.Location = new Point(6, 63);
            CharNameLabel.Name = "CharNameLabel";
            CharNameLabel.Size = new Size(93, 15);
            CharNameLabel.TabIndex = 3;
            CharNameLabel.Text = "Character Name";
            // 
            // EmailSearch
            // 
            EmailSearch.Location = new Point(6, 213);
            EmailSearch.Name = "EmailSearch";
            EmailSearch.Size = new Size(122, 23);
            EmailSearch.TabIndex = 8;
            // 
            // CharIDLabel
            // 
            CharIDLabel.AutoSize = true;
            CharIDLabel.Location = new Point(6, 151);
            CharIDLabel.Name = "CharIDLabel";
            CharIDLabel.Size = new Size(72, 15);
            CharIDLabel.TabIndex = 7;
            CharIDLabel.Text = "Character ID";
            // 
            // CharNameSearch
            // 
            CharNameSearch.Location = new Point(6, 81);
            CharNameSearch.Name = "CharNameSearch";
            CharNameSearch.Size = new Size(122, 23);
            CharNameSearch.TabIndex = 1;
            // 
            // tableLayoutPanel1
            // 
            tableLayoutPanel1.ColumnCount = 2;
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 10.63049F));
            tableLayoutPanel1.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 89.3695145F));
            tableLayoutPanel1.Controls.Add(DatabaseTabs, 1, 0);
            tableLayoutPanel1.Controls.Add(label2, 0, 0);
            tableLayoutPanel1.Dock = DockStyle.Fill;
            tableLayoutPanel1.Location = new Point(0, 24);
            tableLayoutPanel1.Name = "tableLayoutPanel1";
            tableLayoutPanel1.RowCount = 1;
            tableLayoutPanel1.RowStyles.Add(new RowStyle(SizeType.Percent, 100F));
            tableLayoutPanel1.RowStyles.Add(new RowStyle(SizeType.Absolute, 20F));
            tableLayoutPanel1.Size = new Size(1264, 652);
            tableLayoutPanel1.TabIndex = 3;
            // 
            // label2
            // 
            label2.AutoSize = true;
            label2.Dock = DockStyle.Top;
            label2.Location = new Point(3, 0);
            label2.Name = "label2";
            label2.Size = new Size(128, 15);
            label2.TabIndex = 3;
            label2.Text = "Table Search";
            label2.TextAlign = ContentAlignment.MiddleCenter;
            // 
            // treeView1
            // 
            treeView1.Dock = DockStyle.Fill;
            treeView1.FullRowSelect = true;
            treeView1.Location = new Point(3, 3);
            treeView1.Name = "treeView1";
            treeView1.Size = new Size(28, 630);
            treeView1.TabIndex = 0;
            // 
            // dataGridView1
            // 
            dataGridView1.BackgroundColor = SystemColors.Window;
            dataGridView1.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            dataGridView1.Dock = DockStyle.Fill;
            dataGridView1.GridColor = SystemColors.Window;
            dataGridView1.Location = new Point(37, 3);
            dataGridView1.Name = "dataGridView1";
            dataGridView1.Size = new Size(160, 630);
            dataGridView1.TabIndex = 1;
            // 
            // tableLayoutPanel3
            // 
            tableLayoutPanel3.ColumnCount = 2;
            tableLayoutPanel3.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel3.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel3.Controls.Add(treeView1, 0, 0);
            tableLayoutPanel3.Dock = DockStyle.Fill;
            tableLayoutPanel3.Location = new Point(0, 0);
            tableLayoutPanel3.Name = "tableLayoutPanel3";
            tableLayoutPanel3.RowCount = 1;
            tableLayoutPanel3.Size = new Size(200, 100);
            tableLayoutPanel3.TabIndex = 0;
            // 
            // treeView5
            // 
            treeView5.Dock = DockStyle.Fill;
            treeView5.FullRowSelect = true;
            treeView5.Location = new Point(3, 3);
            treeView5.Name = "treeView5";
            treeView5.Size = new Size(28, 630);
            treeView5.TabIndex = 0;
            // 
            // dataGridView5
            // 
            dataGridView5.BackgroundColor = SystemColors.Window;
            dataGridView5.ColumnHeadersHeightSizeMode = DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            dataGridView5.Dock = DockStyle.Fill;
            dataGridView5.GridColor = SystemColors.Window;
            dataGridView5.Location = new Point(37, 3);
            dataGridView5.Name = "dataGridView5";
            dataGridView5.Size = new Size(160, 630);
            dataGridView5.TabIndex = 1;
            // 
            // tableLayoutPanel7
            // 
            tableLayoutPanel7.ColumnCount = 2;
            tableLayoutPanel7.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 17.3553715F));
            tableLayoutPanel7.ColumnStyles.Add(new ColumnStyle(SizeType.Percent, 82.64463F));
            tableLayoutPanel7.Controls.Add(treeView5, 0, 0);
            tableLayoutPanel7.Dock = DockStyle.Fill;
            tableLayoutPanel7.Location = new Point(0, 0);
            tableLayoutPanel7.Name = "tableLayoutPanel7";
            tableLayoutPanel7.RowCount = 1;
            tableLayoutPanel7.Size = new Size(200, 100);
            tableLayoutPanel7.TabIndex = 0;
            // 
            // menuStrip1
            // 
            menuStrip1.BackColor = Color.Transparent;
            menuStrip1.Items.AddRange(new ToolStripItem[] { tsmDB_Settings, tsm_KeyWarning, tsm_TableDescription });
            menuStrip1.Location = new Point(0, 0);
            menuStrip1.Name = "menuStrip1";
            menuStrip1.Size = new Size(1264, 24);
            menuStrip1.TabIndex = 4;
            menuStrip1.Text = "menuStrip1";
            // 
            // tsmDB_Settings
            // 
            tsmDB_Settings.Name = "tsmDB_Settings";
            tsmDB_Settings.Size = new Size(112, 20);
            tsmDB_Settings.Text = "Database Settings";
            tsmDB_Settings.Click += tsmDB_Settings_Click;
            // 
            // tsm_KeyWarning
            // 
            tsm_KeyWarning.BackColor = SystemColors.Control;
            tsm_KeyWarning.BorderStyle = BorderStyle.None;
            tsm_KeyWarning.Name = "tsm_KeyWarning";
            tsm_KeyWarning.ReadOnly = true;
            tsm_KeyWarning.Size = new Size(320, 20);
            tsm_KeyWarning.Text = "tsm_KeyWarning";
            // 
            // tsm_TableDescription
            // 
            tsm_TableDescription.Alignment = ToolStripItemAlignment.Right;
            tsm_TableDescription.BackColor = SystemColors.Control;
            tsm_TableDescription.BorderStyle = BorderStyle.None;
            tsm_TableDescription.Name = "tsm_TableDescription";
            tsm_TableDescription.ReadOnly = true;
            tsm_TableDescription.Size = new Size(800, 20);
            tsm_TableDescription.Text = "tsm_TableDescription";
            tsm_TableDescription.TextBoxTextAlign = HorizontalAlignment.Right;
            // 
            // TableSearchBox
            // 
            TableSearchBox.Location = new Point(7, 50);
            TableSearchBox.Name = "TableSearchBox";
            TableSearchBox.Size = new Size(120, 23);
            TableSearchBox.TabIndex = 5;
            // 
            // DatabaseForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1264, 676);
            Controls.Add(TableSearchBox);
            Controls.Add(tableLayoutPanel1);
            Controls.Add(menuStrip1);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MainMenuStrip = menuStrip1;
            Name = "DatabaseForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Database Editor";
            Load += DatabaseForm_Load;
            DatabaseTabs.ResumeLayout(false);
            tabAdmin.ResumeLayout(false);
            tableLayoutPanel2.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)AdminData).EndInit();
            tabDevice.ResumeLayout(false);
            tableLayoutPanel4.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)DeviceData).EndInit();
            tabFront.ResumeLayout(false);
            tableLayoutPanel5.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)FrontData).EndInit();
            tabGame.ResumeLayout(false);
            tableLayoutPanel6.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)GameData).EndInit();
            tabUser.ResumeLayout(false);
            tableLayoutPanel8.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)UserData).EndInit();
            tabPlayerSearch.ResumeLayout(false);
            PlayersBox.ResumeLayout(false);
            PlayersBox.PerformLayout();
            tableLayoutPanel1.ResumeLayout(false);
            tableLayoutPanel1.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)dataGridView1).EndInit();
            tableLayoutPanel3.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)dataGridView5).EndInit();
            tableLayoutPanel7.ResumeLayout(false);
            menuStrip1.ResumeLayout(false);
            menuStrip1.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private TreeView AdminTree;
        private TabControl DatabaseTabs;
        private GroupBox PlayersBox;
        private Label AccountIDLabel;
        private TextBox CharNameSearch;
        private TextBox AccountIDSearch;
        private Label CharNameLabel;
        private Label AccountNameLabel;
        private TextBox AccountNameSearch;
        private Label CharIDLabel;
        private TextBox CharIDSearch;
        private Label EmailLabel;
        private TextBox EmailSearch;
        private TableLayoutPanel tableLayoutPanel1;
        private TabPage tabAdmin;
        private DataGridView AdminData;
        private TabPage tabDevice;
        private TabPage tabFront;
        private TabPage tabGame;
        private TabPage tabUser;
        private TableLayoutPanel tableLayoutPanel2;
        private TableLayoutPanel tableLayoutPanel4;
        private TreeView DeviceTree;
        private DataGridView DeviceData;
        private TableLayoutPanel tableLayoutPanel5;
        private TreeView FrontTree;
        private DataGridView FrontData;
        private TableLayoutPanel tableLayoutPanel6;
        private TreeView GameTree;
        private DataGridView GameData;
        private TableLayoutPanel tableLayoutPanel8;
        private TreeView UserTree;
        private DataGridView UserData;
        private TreeView treeView1;
        private DataGridView dataGridView1;
        private TableLayoutPanel tableLayoutPanel3;
        private TreeView treeView5;
        private DataGridView dataGridView5;
        private TableLayoutPanel tableLayoutPanel7;
        private Button btnFilter;
        private MenuStrip menuStrip1;
        private ToolStripMenuItem tsmDB_Settings;
        private Label label1;
        private ListBox lb_IgnoredList;
        private TabPage tabPlayerSearch;
        private ToolStripTextBox tsm_KeyWarning;
        private ToolStripTextBox tsm_TableDescription;
        private Label label2;
        private TextBox TableSearchBox;
    }
}