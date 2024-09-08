using System;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;

namespace Server_Console.Database_Tool
{
    partial class DatabaseTool : Form
    {
        private System.Windows.Forms.Timer resizeTimer;
        private System.ComponentModel.IContainer components = null;
        private MenuStrip menuStrip1;
        private ToolStripMenuItem languageMenuItem;
        private TabControl tabControl1;
        private TabPage MonstersPage;
        private TabPage AchievementsPage;
        private TabPage SkillsPage;
        private TabPage CommandsPage;
        private ItemPage ItemsPage;
        private StatusStrip statusStrip;
        public static ToolStripStatusLabel statusLabel;
        public static ProgressBar progressBar;
        private GroupBox logGroupBox;
        public TextBox logTextBox;

        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        private void InitializeComponent()
        {
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(DatabaseTool));

            // Initialize components
            menuStrip1 = new MenuStrip();
            languageMenuItem = new ToolStripMenuItem();
            tabControl1 = new TabControl();
            ItemsPage = new ItemPage();
            MonstersPage = new TabPage();
            AchievementsPage = new TabPage();
            SkillsPage = new TabPage();
            CommandsPage = new CommandPage();
            statusStrip = new StatusStrip();
            statusLabel = new ToolStripStatusLabel();
            progressBar = new ProgressBar();
            logGroupBox = new GroupBox();
            logTextBox = new TextBox();
            resizeTimer = new System.Windows.Forms.Timer();

            // Configure resize timer
            resizeTimer.Interval = 100;
            resizeTimer.Tick += ResizeTimer_Tick;

            // Configure Form
            SuspendLayout();
            this.BackColor = Color.White;
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1366, 768);
            Controls.Add(tabControl1);
            Controls.Add(logGroupBox);
            Controls.Add(statusStrip);
            Controls.Add(menuStrip1);
            Controls.Add(progressBar);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MainMenuStrip = menuStrip1;
            Name = "DatabaseTool";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Database Tool";
            Load += DatabaseTool_Load;
            this.ResizeEnd += new EventHandler(TabControl1_ResizeEnd);

            // Configure MenuStrip
            menuStrip1.Items.AddRange(new ToolStripItem[] { languageMenuItem });
            menuStrip1.Location = new Point(0, 0);
            menuStrip1.Name = "menuStrip1";
            menuStrip1.Size = new Size(1343, 24);
            menuStrip1.TabIndex = 1;
            menuStrip1.Text = "menuStrip1";
            menuStrip1.BackColor = Color.White;

            // Configure Language MenuItem
            languageMenuItem.Name = "languageMenuItem";
            languageMenuItem.Size = new Size(71, 20);
            languageMenuItem.Text = FileManager.GetStringMessageById(1330206);

            // Configure TabControl
            MapPage.Initialize(tabControl1);
            tabControl1.Controls.Add(ItemsPage);
            tabControl1.Controls.Add(MonstersPage);
            tabControl1.Controls.Add(AchievementsPage);
            tabControl1.Controls.Add(SkillsPage);
            tabControl1.Controls.Add(CommandsPage);
            tabControl1.Dock = DockStyle.Fill;
            tabControl1.Location = new Point(0, 24);
            tabControl1.Name = "tabControl1";
            tabControl1.SelectedIndex = 0;
            tabControl1.Size = new Size(1343, 539);
            tabControl1.TabIndex = 2;
            tabControl1.Appearance = TabAppearance.Normal;
            tabControl1.ItemSize = new Size(130, 30);
            tabControl1.SizeMode = TabSizeMode.Fixed;
            tabControl1.Font = new Font("Segoe UI", 9F);
            tabControl1.BackColor = Color.White;
            tabControl1.Resize += new EventHandler(TabControl1_Resize);

            ItemsPage.Location = new Point(4, 34);
            ItemsPage.Name = "ItemsPage";
            ItemsPage.Padding = new Padding(3);
            ItemsPage.Size = new Size(1335, 501);
            ItemsPage.TabIndex = 1;
            ItemsPage.Text = "Items";
            ItemsPage.UseVisualStyleBackColor = true;

            MonstersPage.Location = new Point(4, 34);
            MonstersPage.Name = "MonstersPage";
            MonstersPage.Size = new Size(1335, 501);
            MonstersPage.TabIndex = 2;
            MonstersPage.Text = "Monsters";
            MonstersPage.UseVisualStyleBackColor = true;

            AchievementsPage.Location = new Point(4, 34);
            AchievementsPage.Name = "AchievementsPage";
            AchievementsPage.Size = new Size(1335, 501);
            AchievementsPage.TabIndex = 3;
            AchievementsPage.Text = "Achievements";
            AchievementsPage.UseVisualStyleBackColor = true;

            SkillsPage.Location = new Point(4, 34);
            SkillsPage.Name = "SkillsPage";
            SkillsPage.Size = new Size(1335, 501);
            SkillsPage.TabIndex = 4;
            SkillsPage.Text = "Skills";
            SkillsPage.UseVisualStyleBackColor = true;

            CommandsPage.Location = new Point(4, 34);
            CommandsPage.Name = "CommandsPage";
            CommandsPage.Size = new Size(1335, 501);
            CommandsPage.TabIndex = 5;
            CommandsPage.Text = "Commands";
            CommandsPage.UseVisualStyleBackColor = true;

            // Configure StatusStrip
            statusStrip.Items.AddRange(new ToolStripItem[] { statusLabel });
            statusStrip.Location = new Point(0, 641);
            statusStrip.Name = "statusStrip";
            statusStrip.Size = new Size(1343, 22);
            statusStrip.TabIndex = 3;
            statusStrip.Text = "statusStrip1";
            statusStrip.BackColor = Color.White;

            // Configure StatusLabel
            statusLabel.Name = "statusLabel";
            statusLabel.Size = new Size(118, 17);
            statusLabel.Text = "Loading";

            // Configure ProgressBar
            progressBar.Dock = DockStyle.Bottom;
            progressBar.TabIndex = 5;
            progressBar.Style = ProgressBarStyle.Continuous;

            // Configure Log GroupBox
            logGroupBox.Dock = DockStyle.Bottom;
            logGroupBox.Controls.Add(logTextBox);
            logGroupBox.Font = new Font("Segoe UI", 9F);
            logGroupBox.Location = new Point(0, 489);
            logGroupBox.Name = "logGroupBox";
            logGroupBox.Size = new Size(1343, 152);
            logGroupBox.TabIndex = 4;
            logGroupBox.TabStop = false;
            logGroupBox.Text = "Log";
            logGroupBox.Padding = new Padding(3);

            // Configure Log TextBox
            logTextBox.Dock = DockStyle.Fill;
            logTextBox.Multiline = true;
            logTextBox.ScrollBars = ScrollBars.Vertical;
            logTextBox.Name = "logTextBox";
            logTextBox.ReadOnly = true;
            logTextBox.BackColor = Color.White;
            logTextBox.ForeColor = Color.Black;
            logTextBox.Font = new Font("Segoe UI", 9F);

            // Finalize Form layout
            menuStrip1.ResumeLayout(false);
            menuStrip1.PerformLayout();
            tabControl1.ResumeLayout(false);
            statusStrip.ResumeLayout(false);
            statusStrip.PerformLayout();
            logGroupBox.ResumeLayout(false);
            logGroupBox.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }

        private void TabControl1_Resize(object sender, EventArgs e)
        {
            resizeTimer.Stop();
            resizeTimer.Start();
        }

        private void TabControl1_ResizeEnd(object sender, EventArgs e)
        {
            UpdateTabItemSize();
        }

        private void ResizeTimer_Tick(object sender, EventArgs e)
        {
            resizeTimer.Stop();
            UpdateTabItemSize();
        }
    }
}
