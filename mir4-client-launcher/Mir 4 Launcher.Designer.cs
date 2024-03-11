namespace Mir_4_Launcher
{
    partial class Launcher
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(Launcher));
            LOMCNLABEL = new Label();
            BoxImage = new PictureBox();
            LOMCNCoLabel = new Label();
            CommunityImage = new PictureBox();
            SettingsBox = new PictureBox();
            CommunityLabel = new Label();
            SettingsLabel = new Label();
            MinimizeImage = new PictureBox();
            CloseImage = new PictureBox();
            GameStartButton1 = new PictureBox();
            GameStartButton2 = new PictureBox();
            ProgressBarImage = new PictureBox();
            DownloadPercentLabel = new Label();
            GBLabel = new Label();
            TimeRemainingLabel = new Label();
            VersionLabel = new Label();
            SpeedLabel = new Label();
            TimeLabel = new Label();
            InfoImage = new PictureBox();
            ProcessCheckTimer = new System.Windows.Forms.Timer(components);
            backgroundWorker1 = new System.ComponentModel.BackgroundWorker();
            WebView = new Microsoft.Web.WebView2.WinForms.WebView2();
            ((System.ComponentModel.ISupportInitialize)BoxImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)CommunityImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)SettingsBox).BeginInit();
            ((System.ComponentModel.ISupportInitialize)MinimizeImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)CloseImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GameStartButton1).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GameStartButton2).BeginInit();
            ((System.ComponentModel.ISupportInitialize)ProgressBarImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)InfoImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)WebView).BeginInit();
            SuspendLayout();
            // 
            // LOMCNLABEL
            // 
            LOMCNLABEL.AutoSize = true;
            LOMCNLABEL.BackColor = Color.Transparent;
            LOMCNLABEL.Font = new Font("Segoe UI Black", 9F, FontStyle.Bold);
            LOMCNLABEL.ForeColor = Color.White;
            LOMCNLABEL.Location = new Point(12, 12);
            LOMCNLABEL.Name = "LOMCNLABEL";
            LOMCNLABEL.Size = new Size(52, 15);
            LOMCNLABEL.TabIndex = 1;
            LOMCNLABEL.Text = "LOMCN";
            LOMCNLABEL.Click += LOMCNLABEL_Click;
            // 
            // BoxImage
            // 
            BoxImage.Image = Properties.Resources.Box;
            BoxImage.Location = new Point(12, 494);
            BoxImage.Name = "BoxImage";
            BoxImage.Size = new Size(740, 100);
            BoxImage.TabIndex = 3;
            BoxImage.TabStop = false;
            // 
            // LOMCNCoLabel
            // 
            LOMCNCoLabel.AutoSize = true;
            LOMCNCoLabel.BackColor = Color.Transparent;
            LOMCNCoLabel.Font = new Font("Segoe UI", 9F, FontStyle.Bold);
            LOMCNCoLabel.ForeColor = Color.DimGray;
            LOMCNCoLabel.Location = new Point(416, 599);
            LOMCNCoLabel.Name = "LOMCNCoLabel";
            LOMCNCoLabel.Size = new Size(219, 15);
            LOMCNCoLabel.TabIndex = 4;
            LOMCNCoLabel.Text = "© LOMCN Co., LTD. All rights reserved.";
            // 
            // CommunityImage
            // 
            CommunityImage.BackColor = Color.Black;
            CommunityImage.BackgroundImageLayout = ImageLayout.None;
            CommunityImage.Image = Properties.Resources.Community;
            CommunityImage.Location = new Point(700, 10);
            CommunityImage.Name = "CommunityImage";
            CommunityImage.Size = new Size(28, 21);
            CommunityImage.TabIndex = 5;
            CommunityImage.TabStop = false;
            CommunityImage.Click += CommunityImage_Click;
            // 
            // SettingsBox
            // 
            SettingsBox.BackColor = Color.Black;
            SettingsBox.Image = Properties.Resources.Settings;
            SettingsBox.Location = new Point(816, 8);
            SettingsBox.Name = "SettingsBox";
            SettingsBox.Size = new Size(24, 24);
            SettingsBox.TabIndex = 6;
            SettingsBox.TabStop = false;
            SettingsBox.Click += SettingsBox_Click;
            // 
            // CommunityLabel
            // 
            CommunityLabel.AutoSize = true;
            CommunityLabel.BackColor = Color.Black;
            CommunityLabel.Font = new Font("Segoe UI", 8F);
            CommunityLabel.ForeColor = SystemColors.Control;
            CommunityLabel.Location = new Point(734, 15);
            CommunityLabel.Name = "CommunityLabel";
            CommunityLabel.Size = new Size(65, 13);
            CommunityLabel.TabIndex = 7;
            CommunityLabel.Text = "Community";
            CommunityLabel.Click += CommunityLabel_Click;
            // 
            // SettingsLabel
            // 
            SettingsLabel.AutoSize = true;
            SettingsLabel.BackColor = Color.Black;
            SettingsLabel.Font = new Font("Segoe UI", 8F);
            SettingsLabel.ForeColor = SystemColors.Control;
            SettingsLabel.Location = new Point(846, 15);
            SettingsLabel.Name = "SettingsLabel";
            SettingsLabel.Size = new Size(49, 13);
            SettingsLabel.TabIndex = 8;
            SettingsLabel.Text = "Settings";
            SettingsLabel.Click += SettingsLabel_Click;
            // 
            // MinimizeImage
            // 
            MinimizeImage.BackgroundImageLayout = ImageLayout.Center;
            MinimizeImage.Image = Properties.Resources.Minimize;
            MinimizeImage.Location = new Point(950, 20);
            MinimizeImage.Name = "MinimizeImage";
            MinimizeImage.Size = new Size(20, 10);
            MinimizeImage.TabIndex = 9;
            MinimizeImage.TabStop = false;
            MinimizeImage.Click += MinimizeImage_Click;
            // 
            // CloseImage
            // 
            CloseImage.BackgroundImageLayout = ImageLayout.Center;
            CloseImage.Image = Properties.Resources.Close;
            CloseImage.Location = new Point(984, 16);
            CloseImage.Name = "CloseImage";
            CloseImage.Size = new Size(15, 15);
            CloseImage.TabIndex = 10;
            CloseImage.TabStop = false;
            CloseImage.Click += CloseImage_Click;
            // 
            // GameStartButton1
            // 
            GameStartButton1.Image = Properties.Resources.Start;
            GameStartButton1.Location = new Point(763, 498);
            GameStartButton1.Name = "GameStartButton1";
            GameStartButton1.Size = new Size(122, 92);
            GameStartButton1.TabIndex = 11;
            GameStartButton1.TabStop = false;
            GameStartButton1.Click += GameStartButton1_Click;
            // 
            // GameStartButton2
            // 
            GameStartButton2.Image = Properties.Resources.Start2;
            GameStartButton2.Location = new Point(894, 498);
            GameStartButton2.Name = "GameStartButton2";
            GameStartButton2.Size = new Size(122, 92);
            GameStartButton2.TabIndex = 12;
            GameStartButton2.TabStop = false;
            GameStartButton2.Click += GameStartButton2_Click;
            // 
            // ProgressBarImage
            // 
            ProgressBarImage.Image = Properties.Resources.BlankProgressBar;
            ProgressBarImage.Location = new Point(16, 559);
            ProgressBarImage.Name = "ProgressBarImage";
            ProgressBarImage.Size = new Size(732, 12);
            ProgressBarImage.TabIndex = 13;
            ProgressBarImage.TabStop = false;
            // 
            // DownloadPercentLabel
            // 
            DownloadPercentLabel.BackColor = Color.Transparent;
            DownloadPercentLabel.FlatStyle = FlatStyle.Flat;
            DownloadPercentLabel.Font = new Font("Calibri", 30F, FontStyle.Bold);
            DownloadPercentLabel.ForeColor = Color.RoyalBlue;
            DownloadPercentLabel.Image = Properties.Resources.Backdrop;
            DownloadPercentLabel.Location = new Point(16, 498);
            DownloadPercentLabel.Name = "DownloadPercentLabel";
            DownloadPercentLabel.Size = new Size(113, 46);
            DownloadPercentLabel.TabIndex = 14;
            DownloadPercentLabel.Text = "100%";
            // 
            // GBLabel
            // 
            GBLabel.BackColor = Color.Transparent;
            GBLabel.FlatStyle = FlatStyle.Flat;
            GBLabel.Font = new Font("Calibri", 14F, FontStyle.Bold);
            GBLabel.ForeColor = Color.DarkKhaki;
            GBLabel.Image = Properties.Resources.Backdrop;
            GBLabel.Location = new Point(135, 525);
            GBLabel.Name = "GBLabel";
            GBLabel.Size = new Size(136, 31);
            GBLabel.TabIndex = 15;
            GBLabel.Text = "0.00GB/0.00GB";
            // 
            // TimeRemainingLabel
            // 
            TimeRemainingLabel.BackColor = Color.Transparent;
            TimeRemainingLabel.FlatStyle = FlatStyle.Flat;
            TimeRemainingLabel.Font = new Font("Calibri", 14F, FontStyle.Bold);
            TimeRemainingLabel.ForeColor = Color.RoyalBlue;
            TimeRemainingLabel.Image = Properties.Resources.Backdrop;
            TimeRemainingLabel.Location = new Point(449, 525);
            TimeRemainingLabel.Name = "TimeRemainingLabel";
            TimeRemainingLabel.Size = new Size(139, 31);
            TimeRemainingLabel.TabIndex = 16;
            TimeRemainingLabel.Text = "Time Remaining";
            // 
            // VersionLabel
            // 
            VersionLabel.BackColor = Color.Transparent;
            VersionLabel.FlatStyle = FlatStyle.Flat;
            VersionLabel.Font = new Font("Calibri", 14F, FontStyle.Bold);
            VersionLabel.ForeColor = Color.RoyalBlue;
            VersionLabel.Image = Properties.Resources.Backdrop;
            VersionLabel.Location = new Point(649, 498);
            VersionLabel.Name = "VersionLabel";
            VersionLabel.Size = new Size(99, 31);
            VersionLabel.TabIndex = 17;
            VersionLabel.Text = "ver 0.0.0.0";
            // 
            // SpeedLabel
            // 
            SpeedLabel.BackColor = Color.Transparent;
            SpeedLabel.FlatStyle = FlatStyle.Flat;
            SpeedLabel.Font = new Font("Calibri", 12F, FontStyle.Bold);
            SpeedLabel.ForeColor = Color.RoyalBlue;
            SpeedLabel.Image = Properties.Resources.Backdrop;
            SpeedLabel.Location = new Point(668, 527);
            SpeedLabel.Name = "SpeedLabel";
            SpeedLabel.Size = new Size(83, 25);
            SpeedLabel.TabIndex = 19;
            SpeedLabel.Text = "(0.00MB/s)";
            // 
            // TimeLabel
            // 
            TimeLabel.BackColor = Color.Transparent;
            TimeLabel.FlatStyle = FlatStyle.Flat;
            TimeLabel.Font = new Font("Calibri", 12F, FontStyle.Bold);
            TimeLabel.ForeColor = Color.CornflowerBlue;
            TimeLabel.Image = Properties.Resources.Backdrop;
            TimeLabel.Location = new Point(594, 527);
            TimeLabel.Name = "TimeLabel";
            TimeLabel.Size = new Size(68, 21);
            TimeLabel.TabIndex = 20;
            TimeLabel.Text = "00.00.00";
            // 
            // InfoImage
            // 
            InfoImage.BackColor = Color.Transparent;
            InfoImage.Image = Properties.Resources.Info;
            InfoImage.Location = new Point(910, 10);
            InfoImage.Name = "InfoImage";
            InfoImage.Size = new Size(24, 24);
            InfoImage.TabIndex = 21;
            InfoImage.TabStop = false;
            InfoImage.Click += InfoImage_Click;
            // 
            // ProcessCheckTimer
            // 
            ProcessCheckTimer.Tick += ProcessCheckTimer_Tick;
            // 
            // WebView
            // 
            WebView.AllowExternalDrop = true;
            WebView.CreationProperties = null;
            WebView.DefaultBackgroundColor = Color.White;
            WebView.Location = new Point(0, 40);
            WebView.Name = "WebView";
            WebView.Size = new Size(1026, 439);
            WebView.TabIndex = 22;
            WebView.ZoomFactor = 1D;
            // 
            // Launcher
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.Black;
            ClientSize = new Size(1026, 623);
            Controls.Add(WebView);
            Controls.Add(InfoImage);
            Controls.Add(TimeLabel);
            Controls.Add(SpeedLabel);
            Controls.Add(VersionLabel);
            Controls.Add(TimeRemainingLabel);
            Controls.Add(GBLabel);
            Controls.Add(DownloadPercentLabel);
            Controls.Add(ProgressBarImage);
            Controls.Add(GameStartButton2);
            Controls.Add(GameStartButton1);
            Controls.Add(CloseImage);
            Controls.Add(MinimizeImage);
            Controls.Add(SettingsLabel);
            Controls.Add(CommunityLabel);
            Controls.Add(SettingsBox);
            Controls.Add(CommunityImage);
            Controls.Add(LOMCNCoLabel);
            Controls.Add(BoxImage);
            Controls.Add(LOMCNLABEL);
            ForeColor = Color.Transparent;
            FormBorderStyle = FormBorderStyle.None;
            Icon = (Icon)resources.GetObject("$this.Icon");
            Name = "Launcher";
            Text = "WEGREED";
            MouseDown += Form1_MouseDown;
            MouseMove += Form1_MouseMove;
            MouseUp += Form1_MouseUp;
            ((System.ComponentModel.ISupportInitialize)BoxImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)CommunityImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)SettingsBox).EndInit();
            ((System.ComponentModel.ISupportInitialize)MinimizeImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)CloseImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)GameStartButton1).EndInit();
            ((System.ComponentModel.ISupportInitialize)GameStartButton2).EndInit();
            ((System.ComponentModel.ISupportInitialize)ProgressBarImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)InfoImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)WebView).EndInit();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion
        private Label LOMCNLABEL;
        private PictureBox BoxImage;
        private Label LOMCNCoLabel;
        private PictureBox CommunityImage;
        private PictureBox SettingsBox;
        private Label CommunityLabel;
        private Label SettingsLabel;
        private PictureBox MinimizeImage;
        private PictureBox CloseImage;
        private PictureBox GameStartButton1;
        private PictureBox GameStartButton2;
        private PictureBox ProgressBarImage;
        private Label DownloadPercentLabel;
        private Label GBLabel;
        private Label TimeRemainingLabel;

/* Unmerged change from project 'Mir 4 Launcher'
Before:
        private Label label4;
After:
        private Label VersionLabel;
        private Label TimeLeftLabel;
*/
        private Label VersionLabel;
        private Label SpeedLabel;
        private Label TimeLabel;
        private PictureBox InfoImage;
        private System.Windows.Forms.Timer ProcessCheckTimer;
        private System.ComponentModel.BackgroundWorker backgroundWorker1;
        private Microsoft.Web.WebView2.WinForms.WebView2 WebView;
    }
}
