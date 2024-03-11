namespace Server_Console.Logs
{
    partial class LogForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(LogForm));
            Tabs = new TabControl();
            WorldPage = new TabPage();
            WorldLogsList = new TreeView();
            WorldBox = new RichTextBox();
            GatewayPage = new TabPage();
            GatewayLogsList = new TreeView();
            GatewayBox = new RichTextBox();
            GamePage = new TabPage();
            GameLogsList = new TreeView();
            GameBox = new RichTextBox();
            FrontPage = new TabPage();
            FrontLogsList = new TreeView();
            FrontBox = new RichTextBox();
            ChattingPage = new TabPage();
            ChattingLogsList = new TreeView();
            ChattingBox = new RichTextBox();
            Tabs.SuspendLayout();
            WorldPage.SuspendLayout();
            GatewayPage.SuspendLayout();
            GamePage.SuspendLayout();
            FrontPage.SuspendLayout();
            ChattingPage.SuspendLayout();
            SuspendLayout();
            // 
            // Tabs
            // 
            Tabs.Controls.Add(WorldPage);
            Tabs.Controls.Add(GatewayPage);
            Tabs.Controls.Add(GamePage);
            Tabs.Controls.Add(FrontPage);
            Tabs.Controls.Add(ChattingPage);
            Tabs.Dock = DockStyle.Top;
            Tabs.Location = new Point(0, 0);
            Tabs.Name = "Tabs";
            Tabs.SelectedIndex = 0;
            Tabs.Size = new Size(800, 426);
            Tabs.TabIndex = 0;
            // 
            // WorldPage
            // 
            WorldPage.Controls.Add(WorldLogsList);
            WorldPage.Controls.Add(WorldBox);
            WorldPage.Location = new Point(4, 24);
            WorldPage.Name = "WorldPage";
            WorldPage.Padding = new Padding(3);
            WorldPage.Size = new Size(792, 398);
            WorldPage.TabIndex = 0;
            WorldPage.Text = "World";
            WorldPage.UseVisualStyleBackColor = true;
            // 
            // WorldLogsList
            // 
            WorldLogsList.Dock = DockStyle.Left;
            WorldLogsList.Location = new Point(3, 3);
            WorldLogsList.Name = "WorldLogsList";
            WorldLogsList.Size = new Size(170, 392);
            WorldLogsList.TabIndex = 2;
            // 
            // WorldBox
            // 
            WorldBox.BackColor = SystemColors.Window;
            WorldBox.Dock = DockStyle.Right;
            WorldBox.ForeColor = SystemColors.WindowText;
            WorldBox.Location = new Point(173, 3);
            WorldBox.Name = "WorldBox";
            WorldBox.Size = new Size(616, 392);
            WorldBox.TabIndex = 1;
            WorldBox.Text = "";
            // 
            // GatewayPage
            // 
            GatewayPage.Controls.Add(GatewayLogsList);
            GatewayPage.Controls.Add(GatewayBox);
            GatewayPage.Location = new Point(4, 24);
            GatewayPage.Name = "GatewayPage";
            GatewayPage.Padding = new Padding(3);
            GatewayPage.Size = new Size(792, 398);
            GatewayPage.TabIndex = 1;
            GatewayPage.Text = "Gateway";
            GatewayPage.UseVisualStyleBackColor = true;
            // 
            // GatewayLogsList
            // 
            GatewayLogsList.Dock = DockStyle.Left;
            GatewayLogsList.Location = new Point(3, 3);
            GatewayLogsList.Name = "GatewayLogsList";
            GatewayLogsList.Size = new Size(172, 392);
            GatewayLogsList.TabIndex = 3;
            // 
            // GatewayBox
            // 
            GatewayBox.Dock = DockStyle.Right;
            GatewayBox.Location = new Point(173, 3);
            GatewayBox.Name = "GatewayBox";
            GatewayBox.Size = new Size(616, 392);
            GatewayBox.TabIndex = 2;
            GatewayBox.Text = "";
            // 
            // GamePage
            // 
            GamePage.Controls.Add(GameLogsList);
            GamePage.Controls.Add(GameBox);
            GamePage.Location = new Point(4, 24);
            GamePage.Name = "GamePage";
            GamePage.Size = new Size(792, 398);
            GamePage.TabIndex = 2;
            GamePage.Text = "Game";
            GamePage.UseVisualStyleBackColor = true;
            // 
            // GameLogsList
            // 
            GameLogsList.Dock = DockStyle.Left;
            GameLogsList.Location = new Point(0, 0);
            GameLogsList.Name = "GameLogsList";
            GameLogsList.Size = new Size(171, 398);
            GameLogsList.TabIndex = 4;
            // 
            // GameBox
            // 
            GameBox.Dock = DockStyle.Right;
            GameBox.Location = new Point(170, 0);
            GameBox.Name = "GameBox";
            GameBox.Size = new Size(622, 398);
            GameBox.TabIndex = 3;
            GameBox.Text = "";
            // 
            // FrontPage
            // 
            FrontPage.Controls.Add(FrontLogsList);
            FrontPage.Controls.Add(FrontBox);
            FrontPage.Location = new Point(4, 24);
            FrontPage.Name = "FrontPage";
            FrontPage.Size = new Size(792, 398);
            FrontPage.TabIndex = 3;
            FrontPage.Text = "Front";
            FrontPage.UseVisualStyleBackColor = true;
            // 
            // FrontLogsList
            // 
            FrontLogsList.Dock = DockStyle.Left;
            FrontLogsList.Location = new Point(0, 0);
            FrontLogsList.Name = "FrontLogsList";
            FrontLogsList.Size = new Size(172, 398);
            FrontLogsList.TabIndex = 5;
            // 
            // FrontBox
            // 
            FrontBox.Dock = DockStyle.Right;
            FrontBox.Location = new Point(170, 0);
            FrontBox.Name = "FrontBox";
            FrontBox.Size = new Size(622, 398);
            FrontBox.TabIndex = 4;
            FrontBox.Text = "";
            // 
            // ChattingPage
            // 
            ChattingPage.BackColor = Color.White;
            ChattingPage.Controls.Add(ChattingLogsList);
            ChattingPage.Controls.Add(ChattingBox);
            ChattingPage.ForeColor = Color.Black;
            ChattingPage.Location = new Point(4, 24);
            ChattingPage.Name = "ChattingPage";
            ChattingPage.Size = new Size(792, 398);
            ChattingPage.TabIndex = 4;
            ChattingPage.Text = "Chatting";
            // 
            // ChattingLogsList
            // 
            ChattingLogsList.Dock = DockStyle.Left;
            ChattingLogsList.Location = new Point(0, 0);
            ChattingLogsList.Name = "ChattingLogsList";
            ChattingLogsList.Size = new Size(173, 398);
            ChattingLogsList.TabIndex = 5;
            // 
            // ChattingBox
            // 
            ChattingBox.Dock = DockStyle.Right;
            ChattingBox.Location = new Point(170, 0);
            ChattingBox.Name = "ChattingBox";
            ChattingBox.Size = new Size(622, 398);
            ChattingBox.TabIndex = 4;
            ChattingBox.Text = "";
            // 
            // LogForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackgroundImageLayout = ImageLayout.Stretch;
            ClientSize = new Size(800, 422);
            Controls.Add(Tabs);
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "LogForm";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Server Logs";
            Tabs.ResumeLayout(false);
            WorldPage.ResumeLayout(false);
            GatewayPage.ResumeLayout(false);
            GamePage.ResumeLayout(false);
            FrontPage.ResumeLayout(false);
            ChattingPage.ResumeLayout(false);
            ResumeLayout(false);
        }

        #endregion

        private TabControl Tabs;
        private TabPage WorldPage;
        private TabPage GatewayPage;
        private TabPage GamePage;
        private TabPage FrontPage;
        private TabPage ChattingPage;
        private RichTextBox WorldBox;
        private RichTextBox GatewayBox;
        private RichTextBox GameBox;
        private RichTextBox FrontBox;
        private RichTextBox ChattingBox;
        private Label CloseButton;
        private TreeView WorldLogsList;
        private TreeView GatewayLogsList;
        private TreeView GameLogsList;
        private TreeView FrontLogsList;
        private TreeView ChattingLogsList;
    }
}