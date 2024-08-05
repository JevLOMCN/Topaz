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
            ItemsPage = new TabPage();
            MonstersPage = new TabPage();
            AchievementsPage = new TabPage();
            SkillsPage = new TabPage();
            CommandsPage = new TabPage();
            tabControl1.SuspendLayout();
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
            tabControl1.Name = "tabControl1";
            tabControl1.SelectedIndex = 0;
            tabControl1.Size = new Size(1343, 789);
            tabControl1.TabIndex = 0;
            // 
            // MapsPage
            // 
            MapsPage.Location = new Point(4, 24);
            MapsPage.Name = "MapsPage";
            MapsPage.Padding = new Padding(3);
            MapsPage.Size = new Size(1335, 761);
            MapsPage.TabIndex = 0;
            MapsPage.Text = "Maps";
            MapsPage.UseVisualStyleBackColor = true;
            // 
            // ItemsPage
            // 
            ItemsPage.Location = new Point(4, 24);
            ItemsPage.Name = "ItemsPage";
            ItemsPage.Padding = new Padding(3);
            ItemsPage.Size = new Size(1335, 761);
            ItemsPage.TabIndex = 1;
            ItemsPage.Text = "Items";
            ItemsPage.UseVisualStyleBackColor = true;
            // 
            // MonstersPage
            // 
            MonstersPage.Location = new Point(4, 24);
            MonstersPage.Name = "MonstersPage";
            MonstersPage.Size = new Size(1335, 761);
            MonstersPage.TabIndex = 2;
            MonstersPage.Text = "Monsters";
            MonstersPage.UseVisualStyleBackColor = true;
            // 
            // AchievementsPage
            // 
            AchievementsPage.Location = new Point(4, 24);
            AchievementsPage.Name = "AchievementsPage";
            AchievementsPage.Size = new Size(1335, 761);
            AchievementsPage.TabIndex = 3;
            AchievementsPage.Text = "Achievements";
            AchievementsPage.UseVisualStyleBackColor = true;
            // 
            // SkillsPage
            // 
            SkillsPage.Location = new Point(4, 24);
            SkillsPage.Name = "SkillsPage";
            SkillsPage.Size = new Size(1335, 761);
            SkillsPage.TabIndex = 4;
            SkillsPage.Text = "Skills";
            SkillsPage.UseVisualStyleBackColor = true;
            // 
            // CommandsPage
            // 
            CommandsPage.Location = new Point(4, 24);
            CommandsPage.Name = "CommandsPage";
            CommandsPage.Size = new Size(1335, 761);
            CommandsPage.TabIndex = 5;
            CommandsPage.Text = "Commands";
            CommandsPage.UseVisualStyleBackColor = true;
            // 
            // DatabaseTool
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1343, 789);
            Controls.Add(tabControl1);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "DatabaseTool";
            Text = "Database Tool";
            tabControl1.ResumeLayout(false);
            ResumeLayout(false);
        }

        #endregion

        private TabControl tabControl1;
        private TabPage MapsPage;
        private TabPage ItemsPage;
        private TabPage MonstersPage;
        private TabPage AchievementsPage;
        private TabPage SkillsPage;
        private TabPage CommandsPage;
    }
}