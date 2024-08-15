namespace Server_Console.Database_Tool
{
    partial class DatabaseTool
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(DatabaseTool));
            tabControl1 = new TabControl();
            MapsPage = new TabPage();
            WorldMapBox = new PictureBox();
            ItemsPage = new ItemPage();
            MonstersPage = new TabPage();
            AchievementsPage = new TabPage();
            SkillsPage = new TabPage();
            CommandsPage = new TabPage();
            tabControl1.SuspendLayout();
            MapsPage.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)WorldMapBox).BeginInit();
            SuspendLayout();
            // 
            // tabControl1
            // 
            tabControl1.Controls.Add(MapsPage);
            tabControl1.Controls.Add(ItemsPage);
            tabControl1.Controls.Add(MonstersPage);
            tabControl1.Controls.Add(AchievementsPage);
            tabControl1.Controls.Add(SkillsPage);
            tabControl1.Controls.Add(CommandsPage);
            tabControl1.Dock = DockStyle.Fill;
            tabControl1.Location = new Point(0, 0);
            tabControl1.Margin = new Padding(5, 5, 5, 5);
            tabControl1.Name = "tabControl1";
            tabControl1.SelectedIndex = 0;
            tabControl1.Size = new Size(2110, 1262);
            tabControl1.TabIndex = 0;
            // 
            // MapsPage
            // 
            MapsPage.Controls.Add(WorldMapBox);
            MapsPage.Location = new Point(4, 33);
            MapsPage.Margin = new Padding(5, 5, 5, 5);
            MapsPage.Name = "MapsPage";
            MapsPage.Padding = new Padding(5, 5, 5, 5);
            MapsPage.Size = new Size(2102, 1225);
            MapsPage.TabIndex = 0;
            MapsPage.Text = "Maps";
            MapsPage.UseVisualStyleBackColor = true;
            // 
            // WorldMapBox
            // 
            WorldMapBox.Anchor = AnchorStyles.Bottom | AnchorStyles.Right;
            WorldMapBox.BackgroundImageLayout = ImageLayout.None;
            WorldMapBox.Location = new Point(489, 398);
            WorldMapBox.Margin = new Padding(5, 5, 5, 5);
            WorldMapBox.Name = "WorldMapBox";
            WorldMapBox.Size = new Size(1609, 819);
            WorldMapBox.TabIndex = 0;
            WorldMapBox.TabStop = false;
            // 
            // ItemsPage
            // 
            ItemsPage.Location = new Point(4, 33);
            ItemsPage.Margin = new Padding(5, 5, 5, 5);
            ItemsPage.Name = "ItemsPage";
            ItemsPage.Padding = new Padding(5, 5, 5, 5);
            ItemsPage.Size = new Size(2102, 1225);
            ItemsPage.TabIndex = 1;
            ItemsPage.Text = "Items";
            ItemsPage.UseVisualStyleBackColor = true;
            // 
            // MonstersPage
            // 
            MonstersPage.Location = new Point(4, 33);
            MonstersPage.Margin = new Padding(5, 5, 5, 5);
            MonstersPage.Name = "MonstersPage";
            MonstersPage.Size = new Size(2102, 1225);
            MonstersPage.TabIndex = 2;
            MonstersPage.Text = "Monsters";
            MonstersPage.UseVisualStyleBackColor = true;
            // 
            // AchievementsPage
            // 
            AchievementsPage.Location = new Point(4, 33);
            AchievementsPage.Margin = new Padding(5, 5, 5, 5);
            AchievementsPage.Name = "AchievementsPage";
            AchievementsPage.Size = new Size(2102, 1225);
            AchievementsPage.TabIndex = 3;
            AchievementsPage.Text = "Achievements";
            AchievementsPage.UseVisualStyleBackColor = true;
            // 
            // SkillsPage
            // 
            SkillsPage.Location = new Point(4, 33);
            SkillsPage.Margin = new Padding(5, 5, 5, 5);
            SkillsPage.Name = "SkillsPage";
            SkillsPage.Size = new Size(2102, 1225);
            SkillsPage.TabIndex = 4;
            SkillsPage.Text = "Skills";
            SkillsPage.UseVisualStyleBackColor = true;
            // 
            // CommandsPage
            // 
            CommandsPage.Location = new Point(4, 33);
            CommandsPage.Margin = new Padding(5, 5, 5, 5);
            CommandsPage.Name = "CommandsPage";
            CommandsPage.Size = new Size(2102, 1225);
            CommandsPage.TabIndex = 5;
            CommandsPage.Text = "Commands";
            CommandsPage.UseVisualStyleBackColor = true;
            // 
            // DatabaseTool
            // 
            AutoScaleDimensions = new SizeF(11F, 24F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(2110, 1262);
            Controls.Add(tabControl1);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Margin = new Padding(5, 5, 5, 5);
            Name = "DatabaseTool";
            Text = "Database Tool";
            Load += DatabaseTool_Load;
            tabControl1.ResumeLayout(false);
            MapsPage.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)WorldMapBox).EndInit();
            ResumeLayout(false);
        }

        #endregion

        private TabControl tabControl1;
        private TabPage MapsPage;
        private TabPage MonstersPage;
        private TabPage AchievementsPage;
        private TabPage SkillsPage;
        private TabPage CommandsPage;
        private PictureBox WorldMapBox;
        private ItemPage ItemsPage;
    }
}