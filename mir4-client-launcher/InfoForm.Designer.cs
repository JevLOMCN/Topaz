namespace Mir_4_Launcher
{
    partial class InfoForm
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
            InfoCloseImage = new PictureBox();
            InfoLabel = new Label();
            ScammedLabel = new Label();
            GitHub = new PictureBox();
            LOMCN = new PictureBox();
            RageZone = new PictureBox();
            label1 = new Label();
            ((System.ComponentModel.ISupportInitialize)InfoCloseImage).BeginInit();
            ((System.ComponentModel.ISupportInitialize)GitHub).BeginInit();
            ((System.ComponentModel.ISupportInitialize)LOMCN).BeginInit();
            ((System.ComponentModel.ISupportInitialize)RageZone).BeginInit();
            SuspendLayout();
            // 
            // InfoCloseImage
            // 
            InfoCloseImage.BackColor = Color.Transparent;
            InfoCloseImage.Image = Properties.Resources.Close;
            InfoCloseImage.Location = new Point(584, 12);
            InfoCloseImage.Name = "InfoCloseImage";
            InfoCloseImage.Size = new Size(11, 10);
            InfoCloseImage.TabIndex = 4;
            InfoCloseImage.TabStop = false;
            InfoCloseImage.Click += InfoCloseImage_Click;
            // 
            // InfoLabel
            // 
            InfoLabel.AutoSize = true;
            InfoLabel.BackColor = Color.Transparent;
            InfoLabel.Font = new Font("Segoe UI", 25F);
            InfoLabel.Location = new Point(257, 9);
            InfoLabel.Name = "InfoLabel";
            InfoLabel.Size = new Size(97, 46);
            InfoLabel.TabIndex = 5;
            InfoLabel.Text = "INFO";
            // 
            // ScammedLabel
            // 
            ScammedLabel.AutoSize = true;
            ScammedLabel.Location = new Point(15, 238);
            ScammedLabel.Name = "ScammedLabel";
            ScammedLabel.Size = new Size(569, 15);
            ScammedLabel.TabIndex = 6;
            ScammedLabel.Text = "* IF YOU HAVE PAID FOR THE MIR 4 SOURCE OR ANY TOOLS CREATED BY JEV, YOU HAVE BEEN SCAMMED!";
            // 
            // GitHub
            // 
            GitHub.Image = Properties.Resources.GitHub;
            GitHub.Location = new Point(271, 93);
            GitHub.Name = "GitHub";
            GitHub.Size = new Size(64, 64);
            GitHub.TabIndex = 7;
            GitHub.TabStop = false;
            GitHub.Click += GitHub_Click;
            // 
            // LOMCN
            // 
            LOMCN.Image = Properties.Resources.LOMCN;
            LOMCN.Location = new Point(341, 93);
            LOMCN.Name = "LOMCN";
            LOMCN.Size = new Size(64, 64);
            LOMCN.TabIndex = 8;
            LOMCN.TabStop = false;
            LOMCN.Click += LOMCN_Click;
            // 
            // RageZone
            // 
            RageZone.Image = Properties.Resources.RageZone;
            RageZone.Location = new Point(201, 93);
            RageZone.Name = "RageZone";
            RageZone.Size = new Size(64, 64);
            RageZone.TabIndex = 9;
            RageZone.TabStop = false;
            RageZone.Click += RageZone_Click;
            // 
            // label1
            // 
            label1.AutoSize = true;
            label1.Location = new Point(219, 189);
            label1.Name = "label1";
            label1.Size = new Size(177, 15);
            label1.TabIndex = 10;
            label1.Text = "Contact Email: Jev@LOMCN.net";
            // 
            // InfoForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            BackColor = Color.Black;
            ClientSize = new Size(607, 262);
            Controls.Add(label1);
            Controls.Add(RageZone);
            Controls.Add(LOMCN);
            Controls.Add(GitHub);
            Controls.Add(ScammedLabel);
            Controls.Add(InfoLabel);
            Controls.Add(InfoCloseImage);
            ForeColor = Color.Gray;
            FormBorderStyle = FormBorderStyle.None;
            Name = "InfoForm";
            StartPosition = FormStartPosition.CenterParent;
            Text = "InfoForm";
            ((System.ComponentModel.ISupportInitialize)InfoCloseImage).EndInit();
            ((System.ComponentModel.ISupportInitialize)GitHub).EndInit();
            ((System.ComponentModel.ISupportInitialize)LOMCN).EndInit();
            ((System.ComponentModel.ISupportInitialize)RageZone).EndInit();
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion
        private PictureBox InfoCloseImage;
        private Label InfoLabel;
        private Label ScammedLabel;
        private PictureBox GitHub;
        private PictureBox LOMCN;
        private PictureBox RageZone;
        private Label label1;
    }
}