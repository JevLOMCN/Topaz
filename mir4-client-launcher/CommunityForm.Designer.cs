namespace Mir_4_Launcher
{
    partial class CommunityForm
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
            LOMCNBox = new PictureBox();
            DiscordBox = new PictureBox();
            GithubBox = new PictureBox();
            pictureBox4 = new PictureBox();
            ((System.ComponentModel.ISupportInitialize)LOMCNBox).BeginInit();
            ((System.ComponentModel.ISupportInitialize)DiscordBox).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GithubBox).BeginInit();
            ((System.ComponentModel.ISupportInitialize)pictureBox4).BeginInit();
            SuspendLayout();
            // 
            // LOMCNBox
            // 
            LOMCNBox.BackgroundImage = Properties.Resources.CommunityButtonBlank;
            LOMCNBox.Dock = DockStyle.Top;
            LOMCNBox.Image = Properties.Resources.LOMCNButton;
            LOMCNBox.Location = new Point(0, 0);
            LOMCNBox.Name = "LOMCNBox";
            LOMCNBox.Size = new Size(198, 38);
            LOMCNBox.TabIndex = 0;
            LOMCNBox.TabStop = false;
            LOMCNBox.Click += LOMCNBox_Click;
            LOMCNBox.MouseLeave += LOMCNBox_MouseLeave;
            LOMCNBox.MouseHover += LOMCNBox_MouseHover;
            // 
            // DiscordBox
            // 
            DiscordBox.BackgroundImage = Properties.Resources.CommunityButtonBlank;
            DiscordBox.Image = Properties.Resources.DiscordButton;
            DiscordBox.Location = new Point(0, 38);
            DiscordBox.Name = "DiscordBox";
            DiscordBox.Size = new Size(198, 38);
            DiscordBox.TabIndex = 1;
            DiscordBox.TabStop = false;
            DiscordBox.Click += DiscordBox_Click;
            DiscordBox.MouseLeave += DiscordBox_MouseLeave;
            DiscordBox.MouseHover += DiscordBox_MouseHover;
            // 
            // GithubBox
            // 
            GithubBox.BackgroundImage = Properties.Resources.CommunityButtonBlank;
            GithubBox.Image = Properties.Resources.GithubButton;
            GithubBox.Location = new Point(0, 76);
            GithubBox.Name = "GithubBox";
            GithubBox.Size = new Size(198, 38);
            GithubBox.TabIndex = 2;
            GithubBox.TabStop = false;
            GithubBox.Click += GithubBox_Click;
            GithubBox.MouseLeave += GithubBox_MouseLeave;
            GithubBox.MouseHover += GithubBox_MouseHover;
            // 
            // pictureBox4
            // 
            pictureBox4.BackgroundImage = Properties.Resources.CommunityButtonBlank;
            pictureBox4.Location = new Point(0, 114);
            pictureBox4.Name = "pictureBox4";
            pictureBox4.Size = new Size(198, 38);
            pictureBox4.TabIndex = 3;
            pictureBox4.TabStop = false;
            // 
            // CommunityForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.Black;
            ClientSize = new Size(198, 153);
            ControlBox = false;
            Controls.Add(pictureBox4);
            Controls.Add(GithubBox);
            Controls.Add(DiscordBox);
            Controls.Add(LOMCNBox);
            FormBorderStyle = FormBorderStyle.None;
            Location = new Point(690, 40);
            Name = "CommunityForm";
            ShowIcon = false;
            ShowInTaskbar = false;
            StartPosition = FormStartPosition.Manual;
            Deactivate += CommunityForm_Deactivate;
            ((System.ComponentModel.ISupportInitialize)LOMCNBox).EndInit();
            ((System.ComponentModel.ISupportInitialize)DiscordBox).EndInit();
            ((System.ComponentModel.ISupportInitialize)GithubBox).EndInit();
            ((System.ComponentModel.ISupportInitialize)pictureBox4).EndInit();
            ResumeLayout(false);
        }

        #endregion

        private PictureBox LOMCNBox;
        private PictureBox DiscordBox;
        private PictureBox GithubBox;
        private PictureBox pictureBox4;
    }
}