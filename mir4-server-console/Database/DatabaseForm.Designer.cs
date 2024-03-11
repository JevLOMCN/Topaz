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
            DBTree = new TreeView();
            DBListView = new ListView();
            DatabaseTabs = new TabControl();
            ViewPage = new TabPage();
            SearchPage = new TabPage();
            AccountsBox = new GroupBox();
            EmailLabel = new Label();
            EmailSearch = new TextBox();
            CharIDLabel = new Label();
            CharIDSearch = new TextBox();
            AccountNameLabel = new Label();
            AccountNameSearch = new TextBox();
            CharNameLabel = new Label();
            AccountIDLabel = new Label();
            CharNameSearch = new TextBox();
            AccountIDSearch = new TextBox();
            DatabaseTabs.SuspendLayout();
            ViewPage.SuspendLayout();
            SearchPage.SuspendLayout();
            AccountsBox.SuspendLayout();
            SuspendLayout();
            // 
            // DBTree
            // 
            DBTree.Dock = DockStyle.Left;
            DBTree.FullRowSelect = true;
            DBTree.Location = new Point(3, 3);
            DBTree.Name = "DBTree";
            DBTree.Size = new Size(269, 642);
            DBTree.TabIndex = 0;
            // 
            // DBListView
            // 
            DBListView.Dock = DockStyle.Right;
            DBListView.Location = new Point(271, 3);
            DBListView.Name = "DBListView";
            DBListView.Size = new Size(982, 642);
            DBListView.TabIndex = 1;
            DBListView.UseCompatibleStateImageBehavior = false;
            // 
            // DatabaseTabs
            // 
            DatabaseTabs.Controls.Add(ViewPage);
            DatabaseTabs.Controls.Add(SearchPage);
            DatabaseTabs.Dock = DockStyle.Fill;
            DatabaseTabs.Location = new Point(0, 0);
            DatabaseTabs.Name = "DatabaseTabs";
            DatabaseTabs.SelectedIndex = 0;
            DatabaseTabs.Size = new Size(1264, 676);
            DatabaseTabs.TabIndex = 2;
            // 
            // ViewPage
            // 
            ViewPage.Controls.Add(DBTree);
            ViewPage.Controls.Add(DBListView);
            ViewPage.Location = new Point(4, 24);
            ViewPage.Name = "ViewPage";
            ViewPage.Padding = new Padding(3);
            ViewPage.Size = new Size(1256, 648);
            ViewPage.TabIndex = 0;
            ViewPage.Text = "View";
            ViewPage.UseVisualStyleBackColor = true;
            // 
            // SearchPage
            // 
            SearchPage.Controls.Add(AccountsBox);
            SearchPage.Location = new Point(4, 24);
            SearchPage.Name = "SearchPage";
            SearchPage.Padding = new Padding(3);
            SearchPage.Size = new Size(1256, 648);
            SearchPage.TabIndex = 1;
            SearchPage.Text = "Search";
            SearchPage.UseVisualStyleBackColor = true;
            // 
            // AccountsBox
            // 
            AccountsBox.Controls.Add(EmailLabel);
            AccountsBox.Controls.Add(EmailSearch);
            AccountsBox.Controls.Add(CharIDLabel);
            AccountsBox.Controls.Add(CharIDSearch);
            AccountsBox.Controls.Add(AccountNameLabel);
            AccountsBox.Controls.Add(AccountNameSearch);
            AccountsBox.Controls.Add(CharNameLabel);
            AccountsBox.Controls.Add(AccountIDLabel);
            AccountsBox.Controls.Add(CharNameSearch);
            AccountsBox.Controls.Add(AccountIDSearch);
            AccountsBox.Location = new Point(8, 6);
            AccountsBox.Name = "AccountsBox";
            AccountsBox.Size = new Size(182, 304);
            AccountsBox.TabIndex = 0;
            AccountsBox.TabStop = false;
            AccountsBox.Text = "Accounts/Characters";
            // 
            // EmailLabel
            // 
            EmailLabel.AutoSize = true;
            EmailLabel.Location = new Point(70, 247);
            EmailLabel.Name = "EmailLabel";
            EmailLabel.Size = new Size(36, 15);
            EmailLabel.TabIndex = 9;
            EmailLabel.Text = "Email";
            // 
            // EmailSearch
            // 
            EmailSearch.Location = new Point(12, 265);
            EmailSearch.Name = "EmailSearch";
            EmailSearch.Size = new Size(155, 23);
            EmailSearch.TabIndex = 8;
            // 
            // CharIDLabel
            // 
            CharIDLabel.AutoSize = true;
            CharIDLabel.Location = new Point(52, 195);
            CharIDLabel.Name = "CharIDLabel";
            CharIDLabel.Size = new Size(72, 15);
            CharIDLabel.TabIndex = 7;
            CharIDLabel.Text = "Character ID";
            // 
            // CharIDSearch
            // 
            CharIDSearch.Location = new Point(40, 213);
            CharIDSearch.Name = "CharIDSearch";
            CharIDSearch.Size = new Size(100, 23);
            CharIDSearch.TabIndex = 6;
            // 
            // AccountNameLabel
            // 
            AccountNameLabel.AutoSize = true;
            AccountNameLabel.Location = new Point(47, 86);
            AccountNameLabel.Name = "AccountNameLabel";
            AccountNameLabel.Size = new Size(87, 15);
            AccountNameLabel.TabIndex = 5;
            AccountNameLabel.Text = "Account Name";
            // 
            // AccountNameSearch
            // 
            AccountNameSearch.Location = new Point(24, 104);
            AccountNameSearch.Name = "AccountNameSearch";
            AccountNameSearch.Size = new Size(130, 23);
            AccountNameSearch.TabIndex = 4;
            // 
            // CharNameLabel
            // 
            CharNameLabel.AutoSize = true;
            CharNameLabel.Location = new Point(45, 139);
            CharNameLabel.Name = "CharNameLabel";
            CharNameLabel.Size = new Size(93, 15);
            CharNameLabel.TabIndex = 3;
            CharNameLabel.Text = "Character Name";
            // 
            // AccountIDLabel
            // 
            AccountIDLabel.AutoSize = true;
            AccountIDLabel.Location = new Point(58, 30);
            AccountIDLabel.Name = "AccountIDLabel";
            AccountIDLabel.Size = new Size(66, 15);
            AccountIDLabel.TabIndex = 2;
            AccountIDLabel.Text = "Account ID";
            // 
            // CharNameSearch
            // 
            CharNameSearch.Location = new Point(24, 157);
            CharNameSearch.Name = "CharNameSearch";
            CharNameSearch.Size = new Size(130, 23);
            CharNameSearch.TabIndex = 1;
            // 
            // AccountIDSearch
            // 
            AccountIDSearch.Location = new Point(40, 48);
            AccountIDSearch.Name = "AccountIDSearch";
            AccountIDSearch.Size = new Size(100, 23);
            AccountIDSearch.TabIndex = 0;
            // 
            // DatabaseForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1264, 676);
            Controls.Add(DatabaseTabs);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "DatabaseForm";
            Text = "Database Editor";
            DatabaseTabs.ResumeLayout(false);
            ViewPage.ResumeLayout(false);
            SearchPage.ResumeLayout(false);
            AccountsBox.ResumeLayout(false);
            AccountsBox.PerformLayout();
            ResumeLayout(false);
        }

        #endregion

        private TreeView DBTree;
        private ListView DBListView;
        private TabControl DatabaseTabs;
        private TabPage ViewPage;
        private TabPage SearchPage;
        private GroupBox AccountsBox;
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
    }
}