namespace Server_Console
{
    partial class MainForm
    {
        /// <summary>
        ///  Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        ///  Clean up any resources being used.
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
        ///  Required method for Designer support - do not modify
        ///  the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            components = new System.ComponentModel.Container();
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(MainForm));
            ExeCheckTimer = new System.Windows.Forms.Timer(components);
            CloseButton = new PictureBox();
            StartAllButton = new PictureBox();
            StopAllButton = new PictureBox();
            ConfigButton = new PictureBox();
            LogsButton = new PictureBox();
            JSONButton = new PictureBox();
            CommandsButton = new PictureBox();
            MonstersButton = new PictureBox();
            ItemsButton = new PictureBox();
            MapsButton = new PictureBox();
            ChattingButton = new PictureBox();
            GatewayButton = new PictureBox();
            GameButton = new PictureBox();
            FrontButton = new PictureBox();
            WorldButton = new PictureBox();
            HomePanel = new Panel();
            LOMCNLogo = new PictureBox();
            RZLogo = new PictureBox();
            DatabaseButton = new PictureBox();
            ((System.ComponentModel.ISupportInitialize)CloseButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)StartAllButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)StopAllButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ConfigButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)LogsButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)JSONButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)CommandsButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)MonstersButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ItemsButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)MapsButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ChattingButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GatewayButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GameButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)FrontButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)WorldButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)LOMCNLogo).BeginInit();
            ((System.ComponentModel.ISupportInitialize)RZLogo).BeginInit();
            ((System.ComponentModel.ISupportInitialize)DatabaseButton).BeginInit();
            SuspendLayout();
            // 
            // CloseButton
            // 
            CloseButton.Anchor = AnchorStyles.Top | AnchorStyles.Right;
            CloseButton.BackColor = Color.Transparent;
            CloseButton.Image = Properties.Resources.Close;
            CloseButton.Location = new Point(1336, 0);
            CloseButton.Name = "CloseButton";
            CloseButton.Size = new Size(28, 28);
            CloseButton.TabIndex = 0;
            CloseButton.TabStop = false;
            CloseButton.Click += CloseButton_Click;
            // 
            // StartAllButton
            // 
            StartAllButton.BackColor = Color.Transparent;
            StartAllButton.Image = Properties.Resources.Start_server;
            StartAllButton.Location = new Point(1, 29);
            StartAllButton.Name = "StartAllButton";
            StartAllButton.Size = new Size(188, 34);
            StartAllButton.TabIndex = 1;
            StartAllButton.TabStop = false;
            StartAllButton.Click += StartAllButton_Click;
            StartAllButton.MouseEnter += StartAllButton_MouseEnter;
            StartAllButton.MouseLeave += StartAllButton_MouseLeave;
            // 
            // StopAllButton
            // 
            StopAllButton.BackColor = Color.Transparent;
            StopAllButton.Image = Properties.Resources.stop_server;
            StopAllButton.Location = new Point(1, 65);
            StopAllButton.Name = "StopAllButton";
            StopAllButton.Size = new Size(188, 34);
            StopAllButton.TabIndex = 2;
            StopAllButton.TabStop = false;
            StopAllButton.Click += StopAllButton_Click;
            StopAllButton.MouseEnter += StopAllButton_MouseEnter;
            StopAllButton.MouseLeave += StopAllButton_MouseLeave;
            // 
            // ConfigButton
            // 
            ConfigButton.BackColor = Color.Transparent;
            ConfigButton.Image = Properties.Resources.config;
            ConfigButton.Location = new Point(1, 102);
            ConfigButton.Name = "ConfigButton";
            ConfigButton.Size = new Size(188, 34);
            ConfigButton.TabIndex = 3;
            ConfigButton.TabStop = false;
            ConfigButton.Click += ConfigButton_Click;
            ConfigButton.MouseEnter += ConfigButton_MouseEnter;
            ConfigButton.MouseLeave += ConfigButton_MouseLeave;
            // 
            // LogsButton
            // 
            LogsButton.BackColor = Color.Transparent;
            LogsButton.Image = Properties.Resources.log;
            LogsButton.Location = new Point(1, 139);
            LogsButton.Name = "LogsButton";
            LogsButton.Size = new Size(188, 34);
            LogsButton.TabIndex = 4;
            LogsButton.TabStop = false;
            LogsButton.Click += LogsButton_Click;
            LogsButton.MouseEnter += LogsButton_MouseEnter;
            LogsButton.MouseLeave += LogsButton_MouseLeave;
            // 
            // JSONButton
            // 
            JSONButton.BackColor = Color.Transparent;
            JSONButton.Image = Properties.Resources.json;
            JSONButton.Location = new Point(1, 175);
            JSONButton.Name = "JSONButton";
            JSONButton.Size = new Size(188, 34);
            JSONButton.TabIndex = 5;
            JSONButton.TabStop = false;
            JSONButton.Click += JSONButton_Click;
            JSONButton.MouseEnter += JSONButton_MouseEnter;
            JSONButton.MouseLeave += JSONButton_MouseLeave;
            // 
            // CommandsButton
            // 
            CommandsButton.BackColor = Color.Transparent;
            CommandsButton.Image = Properties.Resources.commands;
            CommandsButton.Location = new Point(1, 394);
            CommandsButton.Name = "CommandsButton";
            CommandsButton.Size = new Size(188, 34);
            CommandsButton.TabIndex = 9;
            CommandsButton.TabStop = false;
            CommandsButton.Click += CommandsButton_Click;
            CommandsButton.MouseEnter += CommandsButton_MouseEnter;
            CommandsButton.MouseLeave += CommandsButton_MouseLeave;
            // 
            // MonstersButton
            // 
            MonstersButton.BackColor = Color.Transparent;
            MonstersButton.Image = Properties.Resources.monsters;
            MonstersButton.Location = new Point(1, 360);
            MonstersButton.Name = "MonstersButton";
            MonstersButton.Size = new Size(188, 34);
            MonstersButton.TabIndex = 8;
            MonstersButton.TabStop = false;
            MonstersButton.Click += MonstersButton_Click;
            MonstersButton.MouseEnter += MonstersButton_MouseEnter;
            MonstersButton.MouseLeave += MonstersButton_MouseLeave;
            // 
            // ItemsButton
            // 
            ItemsButton.BackColor = Color.Transparent;
            ItemsButton.Image = Properties.Resources.items;
            ItemsButton.Location = new Point(1, 325);
            ItemsButton.Name = "ItemsButton";
            ItemsButton.Size = new Size(188, 34);
            ItemsButton.TabIndex = 7;
            ItemsButton.TabStop = false;
            ItemsButton.Click += ItemsButton_Click;
            ItemsButton.MouseEnter += ItemsButton_MouseEnter;
            ItemsButton.MouseLeave += ItemsButton_MouseLeave;
            // 
            // MapsButton
            // 
            MapsButton.BackColor = Color.Transparent;
            MapsButton.Image = Properties.Resources.maps;
            MapsButton.Location = new Point(1, 290);
            MapsButton.Name = "MapsButton";
            MapsButton.Size = new Size(188, 34);
            MapsButton.TabIndex = 6;
            MapsButton.TabStop = false;
            MapsButton.Click += MapsButton_Click;
            MapsButton.MouseEnter += MapsButton_MouseEnter;
            MapsButton.MouseLeave += MapsButton_MouseLeave;
            // 
            // ChattingButton
            // 
            ChattingButton.BackColor = Color.Transparent;
            ChattingButton.Image = Properties.Resources.chatting;
            ChattingButton.Location = new Point(1, 585);
            ChattingButton.Name = "ChattingButton";
            ChattingButton.Size = new Size(188, 34);
            ChattingButton.TabIndex = 13;
            ChattingButton.TabStop = false;
            ChattingButton.Click += ChattingButton_Click;
            // 
            // GatewayButton
            // 
            GatewayButton.BackColor = Color.Transparent;
            GatewayButton.Image = Properties.Resources.gateway;
            GatewayButton.Location = new Point(1, 551);
            GatewayButton.Name = "GatewayButton";
            GatewayButton.Size = new Size(188, 34);
            GatewayButton.TabIndex = 12;
            GatewayButton.TabStop = false;
            GatewayButton.Click += GatewayButton_Click;
            // 
            // GameButton
            // 
            GameButton.BackColor = Color.Transparent;
            GameButton.Image = Properties.Resources.game;
            GameButton.Location = new Point(1, 516);
            GameButton.Name = "GameButton";
            GameButton.Size = new Size(188, 34);
            GameButton.TabIndex = 11;
            GameButton.TabStop = false;
            GameButton.Click += GameButton_Click;
            // 
            // FrontButton
            // 
            FrontButton.BackColor = Color.Transparent;
            FrontButton.Image = Properties.Resources.front;
            FrontButton.Location = new Point(1, 481);
            FrontButton.Name = "FrontButton";
            FrontButton.Size = new Size(188, 34);
            FrontButton.TabIndex = 10;
            FrontButton.TabStop = false;
            FrontButton.Click += FrontButton_Click;
            // 
            // WorldButton
            // 
            WorldButton.BackColor = Color.Transparent;
            WorldButton.Image = Properties.Resources.world;
            WorldButton.Location = new Point(1, 618);
            WorldButton.Name = "WorldButton";
            WorldButton.Size = new Size(188, 34);
            WorldButton.TabIndex = 14;
            WorldButton.TabStop = false;
            WorldButton.Click += WorldButton_Click;
            // 
            // HomePanel
            // 
            HomePanel.BackColor = Color.Transparent;
            HomePanel.ForeColor = Color.White;
            HomePanel.Location = new Point(195, 29);
            HomePanel.Name = "HomePanel";
            HomePanel.Size = new Size(802, 740);
            HomePanel.TabIndex = 15;
            // 
            // LOMCNLogo
            // 
            LOMCNLogo.BackColor = Color.Transparent;
            LOMCNLogo.Image = Properties.Resources.LOMCN;
            LOMCNLogo.Location = new Point(1186, 712);
            LOMCNLogo.Name = "LOMCNLogo";
            LOMCNLogo.Size = new Size(177, 54);
            LOMCNLogo.TabIndex = 16;
            LOMCNLogo.TabStop = false;
            LOMCNLogo.Click += LOMCNLogo_Click;
            // 
            // RZLogo
            // 
            RZLogo.BackColor = Color.Transparent;
            RZLogo.Image = Properties.Resources.RageZone;
            RZLogo.Location = new Point(1220, 629);
            RZLogo.Name = "RZLogo";
            RZLogo.Size = new Size(100, 77);
            RZLogo.TabIndex = 17;
            RZLogo.TabStop = false;
            RZLogo.Click += RZLogo_Click;
            // 
            // DatabaseButton
            // 
            DatabaseButton.BackColor = Color.Transparent;
            DatabaseButton.Image = Properties.Resources.sql_;
            DatabaseButton.Location = new Point(1, 211);
            DatabaseButton.Name = "DatabaseButton";
            DatabaseButton.Size = new Size(188, 34);
            DatabaseButton.TabIndex = 18;
            DatabaseButton.TabStop = false;
            DatabaseButton.Click += DatabaseButton_Click;
            DatabaseButton.MouseEnter += DatabaseButton_MouseEnter;
            DatabaseButton.MouseLeave += DatabaseButton_MouseLeave;
            // 
            // MainForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.Black;
            BackgroundImage = Properties.Resources.Main;
            BackgroundImageLayout = ImageLayout.Stretch;
            ClientSize = new Size(1366, 768);
            ControlBox = false;
            Controls.Add(DatabaseButton);
            Controls.Add(RZLogo);
            Controls.Add(LOMCNLogo);
            Controls.Add(HomePanel);
            Controls.Add(WorldButton);
            Controls.Add(ChattingButton);
            Controls.Add(GatewayButton);
            Controls.Add(GameButton);
            Controls.Add(FrontButton);
            Controls.Add(CommandsButton);
            Controls.Add(MonstersButton);
            Controls.Add(ItemsButton);
            Controls.Add(MapsButton);
            Controls.Add(JSONButton);
            Controls.Add(LogsButton);
            Controls.Add(ConfigButton);
            Controls.Add(StopAllButton);
            Controls.Add(StartAllButton);
            Controls.Add(CloseButton);
            DoubleBuffered = true;
            FormBorderStyle = FormBorderStyle.None;
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MaximumSize = new Size(1366, 768);
            MdiChildrenMinimizedAnchorBottom = false;
            MinimizeBox = false;
            Name = "MainForm";
            ShowIcon = false;
            StartPosition = FormStartPosition.CenterScreen;
            MouseDown += MainForm_MouseDown;
            MouseMove += MainForm_MouseMove;
            ((System.ComponentModel.ISupportInitialize)CloseButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)StartAllButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)StopAllButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)ConfigButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)LogsButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)JSONButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)CommandsButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)MonstersButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)ItemsButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)MapsButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)ChattingButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)GatewayButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)GameButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)FrontButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)WorldButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)LOMCNLogo).EndInit();
            ((System.ComponentModel.ISupportInitialize)RZLogo).EndInit();
            ((System.ComponentModel.ISupportInitialize)DatabaseButton).EndInit();
            ResumeLayout(false);
        }

        #endregion
        private System.Windows.Forms.Timer ExeCheckTimer;
        private PictureBox CloseButton;
        private PictureBox StartAllButton;
        private PictureBox StopAllButton;
        private PictureBox ConfigButton;
        private PictureBox LogsButton;
        private PictureBox JSONButton;
        private PictureBox CommandsButton;
        private PictureBox MonstersButton;
        private PictureBox ItemsButton;
        private PictureBox MapsButton;
        private PictureBox ChattingButton;
        private PictureBox GatewayButton;
        private PictureBox GameButton;
        private PictureBox FrontButton;
        private PictureBox WorldButton;
        private Panel HomePanel;
        private PictureBox LOMCNLogo;
        private PictureBox RZLogo;
        private PictureBox DatabaseButton;
    }
}
