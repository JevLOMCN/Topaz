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
            Logo = new PictureBox();
            CloseButton = new Label();
            WorldButton = new PictureBox();
            GatewayButton = new PictureBox();
            GameButton = new PictureBox();
            FrontButton = new PictureBox();
            ChattingButton = new PictureBox();
            ExeCheckTimer = new System.Windows.Forms.Timer(components);
            DatabaseImage = new PictureBox();
            JSONImage = new PictureBox();
            LogsImage = new PictureBox();
            ConfigImage = new PictureBox();
            StartAllButton = new PictureBox();
            ((System.ComponentModel.ISupportInitialize)Logo).BeginInit();
            ((System.ComponentModel.ISupportInitialize)WorldButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GatewayButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GameButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)FrontButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ChattingButton).BeginInit();
            ((System.ComponentModel.ISupportInitialize)DatabaseImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)JSONImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)LogsImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ConfigImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)StartAllButton).BeginInit();
            SuspendLayout();
            // 
            // Logo
            // 
            Logo.BackColor = Color.Transparent;
            Logo.Image = Properties.Resources.Logo;
            Logo.Location = new Point(-45, -12);
            Logo.Name = "Logo";
            Logo.Size = new Size(187, 89);
            Logo.TabIndex = 0;
            Logo.TabStop = false;
            Logo.Click += Logo_Click;
            Logo.MouseLeave += Logo_MouseLeave;
            Logo.MouseHover += Logo_MouseHover;
            // 
            // CloseButton
            // 
            CloseButton.AutoSize = true;
            CloseButton.BackColor = Color.Transparent;
            CloseButton.Font = new Font("Segoe UI", 15F, FontStyle.Bold);
            CloseButton.ForeColor = SystemColors.ControlLightLight;
            CloseButton.Image = Properties.Resources.Close;
            CloseButton.Location = new Point(1169, -1);
            CloseButton.Name = "CloseButton";
            CloseButton.Size = new Size(18, 28);
            CloseButton.TabIndex = 1;
            CloseButton.Text = " ";
            CloseButton.Click += CloseButton_Click;
            CloseButton.MouseLeave += CloseButton_MouseLeave;
            CloseButton.MouseHover += CloseButton_MouseHover;
            // 
            // WorldButton
            // 
            WorldButton.BackColor = Color.Transparent;
            WorldButton.Image = Properties.Resources.WorldOff;
            WorldButton.Location = new Point(2, 347);
            WorldButton.Name = "WorldButton";
            WorldButton.Size = new Size(166, 51);
            WorldButton.TabIndex = 2;
            WorldButton.TabStop = false;
            WorldButton.Click += WorldButton_Click;
            WorldButton.MouseLeave += WorldButton_MouseLeave;
            WorldButton.MouseHover += WorldButton_MouseHover;
            // 
            // GatewayButton
            // 
            GatewayButton.BackColor = Color.Transparent;
            GatewayButton.Image = Properties.Resources.GatewayOff;
            GatewayButton.Location = new Point(2, 404);
            GatewayButton.Name = "GatewayButton";
            GatewayButton.Size = new Size(166, 51);
            GatewayButton.TabIndex = 3;
            GatewayButton.TabStop = false;
            GatewayButton.Click += GatewayButton_Click;
            GatewayButton.MouseLeave += GatewayButton_MouseLeave;
            GatewayButton.MouseHover += GatewayButton_MouseHover;
            // 
            // GameButton
            // 
            GameButton.BackColor = Color.Transparent;
            GameButton.Image = Properties.Resources.GameOff;
            GameButton.Location = new Point(2, 461);
            GameButton.Name = "GameButton";
            GameButton.Size = new Size(166, 51);
            GameButton.TabIndex = 4;
            GameButton.TabStop = false;
            GameButton.Click += GameButton_Click;
            GameButton.MouseLeave += GameButton_MouseLeave;
            GameButton.MouseHover += GameButton_MouseHover;
            // 
            // FrontButton
            // 
            FrontButton.BackColor = Color.Transparent;
            FrontButton.Image = Properties.Resources.FrontOff;
            FrontButton.Location = new Point(2, 518);
            FrontButton.Name = "FrontButton";
            FrontButton.Size = new Size(166, 51);
            FrontButton.TabIndex = 5;
            FrontButton.TabStop = false;
            FrontButton.Click += FrontButton_Click;
            FrontButton.MouseLeave += FrontButton_MouseLeave;
            FrontButton.MouseHover += FrontButton_MouseHover;
            // 
            // ChattingButton
            // 
            ChattingButton.BackColor = Color.Transparent;
            ChattingButton.Image = Properties.Resources.ChatOff;
            ChattingButton.Location = new Point(2, 575);
            ChattingButton.Name = "ChattingButton";
            ChattingButton.Size = new Size(166, 51);
            ChattingButton.TabIndex = 6;
            ChattingButton.TabStop = false;
            ChattingButton.Click += ChattingButton_Click;
            ChattingButton.MouseLeave += ChattingButton_MouseLeave;
            ChattingButton.MouseHover += ChattingButton_MouseHover;
            // 
            // ExeCheckTimer
            // 
            ExeCheckTimer.Tick += ExeCheckTimer_Tick;
            // 
            // DatabaseImage
            // 
            DatabaseImage.BackColor = Color.Transparent;
            DatabaseImage.Image = Properties.Resources.Database;
            DatabaseImage.Location = new Point(174, 570);
            DatabaseImage.Name = "DatabaseImage";
            DatabaseImage.Size = new Size(56, 56);
            DatabaseImage.TabIndex = 7;
            DatabaseImage.TabStop = false;
            DatabaseImage.Click += DatabaseImage_Click;
            DatabaseImage.MouseLeave += DatabaseImage_MouseLeave;
            DatabaseImage.MouseHover += DatabaseImage_MouseHover;
            // 
            // JSONImage
            // 
            JSONImage.BackColor = Color.Transparent;
            JSONImage.Image = Properties.Resources.JSON;
            JSONImage.Location = new Point(170, 500);
            JSONImage.Name = "JSONImage";
            JSONImage.Size = new Size(64, 64);
            JSONImage.TabIndex = 8;
            JSONImage.TabStop = false;
            JSONImage.Click += JSONImage_Click;
            JSONImage.MouseLeave += JSONImage_MouseLeave;
            JSONImage.MouseHover += JSONImage_MouseHover;
            // 
            // LogsImage
            // 
            LogsImage.BackColor = Color.Transparent;
            LogsImage.Image = Properties.Resources.Logs;
            LogsImage.Location = new Point(170, 430);
            LogsImage.Name = "LogsImage";
            LogsImage.Size = new Size(64, 64);
            LogsImage.TabIndex = 9;
            LogsImage.TabStop = false;
            LogsImage.Click += LogsImage_Click;
            LogsImage.MouseLeave += LogsImage_MouseLeave;
            LogsImage.MouseHover += LogsImage_MouseHover;
            // 
            // ConfigImage
            // 
            ConfigImage.BackColor = Color.Transparent;
            ConfigImage.Image = Properties.Resources.Config;
            ConfigImage.Location = new Point(170, 360);
            ConfigImage.Name = "ConfigImage";
            ConfigImage.Size = new Size(64, 64);
            ConfigImage.TabIndex = 10;
            ConfigImage.TabStop = false;
            ConfigImage.Click += ConfigImage_Click;
            ConfigImage.MouseLeave += ConfigImage_MouseLeave;
            ConfigImage.MouseHover += ConfigImage_MouseHover;
            // 
            // StartAllButton
            // 
            StartAllButton.BackColor = Color.Transparent;
            StartAllButton.Image = Properties.Resources.Start_All;
            StartAllButton.Location = new Point(12, 291);
            StartAllButton.Name = "StartAllButton";
            StartAllButton.Size = new Size(149, 50);
            StartAllButton.TabIndex = 11;
            StartAllButton.TabStop = false;
            StartAllButton.Click += StartAllButton_Click;
            StartAllButton.MouseLeave += StartAllButton_MouseLeave;
            StartAllButton.MouseHover += StartAllButton_MouseHover;
            // 
            // MainForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.Black;
            BackgroundImage = Properties.Resources.Wallpaper;
            BackgroundImageLayout = ImageLayout.Stretch;
            ClientSize = new Size(1193, 628);
            ControlBox = false;
            Controls.Add(StartAllButton);
            Controls.Add(ConfigImage);
            Controls.Add(LogsImage);
            Controls.Add(JSONImage);
            Controls.Add(DatabaseImage);
            Controls.Add(ChattingButton);
            Controls.Add(FrontButton);
            Controls.Add(GameButton);
            Controls.Add(GatewayButton);
            Controls.Add(WorldButton);
            Controls.Add(CloseButton);
            Controls.Add(Logo);
            FormBorderStyle = FormBorderStyle.None;
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MaximumSize = new Size(1193, 628);
            MdiChildrenMinimizedAnchorBottom = false;
            MinimizeBox = false;
            Name = "MainForm";
            ShowIcon = false;
            StartPosition = FormStartPosition.CenterScreen;
            ((System.ComponentModel.ISupportInitialize)Logo).EndInit();
            ((System.ComponentModel.ISupportInitialize)WorldButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)GatewayButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)GameButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)FrontButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)ChattingButton).EndInit();
            ((System.ComponentModel.ISupportInitialize)DatabaseImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)JSONImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)LogsImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)ConfigImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)StartAllButton).EndInit();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private PictureBox Logo;
        private Label CloseButton;
        private PictureBox WorldButton;
        private PictureBox GatewayButton;
        private PictureBox GameButton;
        private PictureBox FrontButton;
        private PictureBox ChattingButton;
        private System.Windows.Forms.Timer ExeCheckTimer;
        private PictureBox DatabaseImage;
        private PictureBox JSONImage;
        private PictureBox LogsImage;
        private PictureBox ConfigImage;
        private PictureBox StartAllButton;
    }
}
