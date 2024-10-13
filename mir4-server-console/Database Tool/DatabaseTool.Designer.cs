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
        private Panel mainPanel;
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
            mainPanel = new Panel();
            statusStrip = new StatusStrip();
            statusLabel = new ToolStripStatusLabel();
            progressBar = new ProgressBar();
            logGroupBox = new GroupBox();
            logTextBox = new TextBox();

            // Configure Form
            SuspendLayout();
            this.BackColor = Color.White;
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(1400, 800);
            Controls.Add(mainPanel);
            Controls.Add(logGroupBox);
            Controls.Add(statusStrip);
            Controls.Add(menuStrip1);
            Controls.Add(progressBar);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MainMenuStrip = menuStrip1;
            Name = "DatabaseTool";
            StartPosition = FormStartPosition.CenterScreen;
            Load += DatabaseTool_Load;

            SetTitleBasedOnPageType();

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

            // Configure Panel (Main Content Area)
            mainPanel.Dock = DockStyle.Fill;
            mainPanel.BackColor = Color.White;
            mainPanel.Name = "mainPanel";
            mainPanel.Size = new Size(1400, 600);

            if (pageType == PageType.Maps)
                MapPage.Initialize(this.instanceId, mainPanel);
            else if (pageType == PageType.Items)
                ItemPage.Initialize(this.instanceId, mainPanel);

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
            statusStrip.ResumeLayout(false);
            statusStrip.PerformLayout();
            logGroupBox.ResumeLayout(false);
            logGroupBox.PerformLayout();
            ResumeLayout(false);
            PerformLayout();
        }
    }
}
