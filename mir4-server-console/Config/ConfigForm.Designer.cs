namespace Server_Console.Config
{
    partial class ConfigForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(ConfigForm));
            ConfigTabs = new TabControl();
            ChattingPage = new TabPage();
            FrontPage = new TabPage();
            GamePage = new TabPage();
            GatewayPage = new TabPage();
            WorldPage = new TabPage();
            ConfigTabs.SuspendLayout();
            SuspendLayout();
            // 
            // ConfigTabs
            // 
            ConfigTabs.Controls.Add(ChattingPage);
            ConfigTabs.Controls.Add(FrontPage);
            ConfigTabs.Controls.Add(GamePage);
            ConfigTabs.Controls.Add(GatewayPage);
            ConfigTabs.Controls.Add(WorldPage);
            ConfigTabs.Dock = DockStyle.Fill;
            ConfigTabs.Location = new Point(0, 0);
            ConfigTabs.Name = "ConfigTabs";
            ConfigTabs.SelectedIndex = 0;
            ConfigTabs.Size = new Size(383, 497);
            ConfigTabs.TabIndex = 0;
            // 
            // ChattingPage
            // 
            ChattingPage.Location = new Point(4, 24);
            ChattingPage.Name = "ChattingPage";
            ChattingPage.Padding = new Padding(3);
            ChattingPage.Size = new Size(375, 469);
            ChattingPage.TabIndex = 0;
            ChattingPage.Text = "Chatting";
            ChattingPage.UseVisualStyleBackColor = true;
            // 
            // FrontPage
            // 
            FrontPage.Location = new Point(4, 24);
            FrontPage.Name = "FrontPage";
            FrontPage.Padding = new Padding(3);
            FrontPage.Size = new Size(375, 469);
            FrontPage.TabIndex = 1;
            FrontPage.Text = "Front";
            FrontPage.UseVisualStyleBackColor = true;
            // 
            // GamePage
            // 
            GamePage.Location = new Point(4, 24);
            GamePage.Name = "GamePage";
            GamePage.Size = new Size(375, 469);
            GamePage.TabIndex = 2;
            GamePage.Text = "Game";
            GamePage.UseVisualStyleBackColor = true;
            // 
            // GatewayPage
            // 
            GatewayPage.Location = new Point(4, 24);
            GatewayPage.Name = "GatewayPage";
            GatewayPage.Size = new Size(375, 469);
            GatewayPage.TabIndex = 3;
            GatewayPage.Text = "Gateway";
            GatewayPage.UseVisualStyleBackColor = true;
            // 
            // WorldPage
            // 
            WorldPage.Location = new Point(4, 24);
            WorldPage.Name = "WorldPage";
            WorldPage.Size = new Size(375, 469);
            WorldPage.TabIndex = 4;
            WorldPage.Text = "World";
            WorldPage.UseVisualStyleBackColor = true;
            // 
            // ConfigForm
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(383, 497);
            Controls.Add(ConfigTabs);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MinimizeBox = false;
            Name = "ConfigForm";
            Text = "Server Configs";
            ConfigTabs.ResumeLayout(false);
            ResumeLayout(false);
        }

        #endregion

        private TabControl ConfigTabs;
        private TabPage ChattingPage;
        private TabPage FrontPage;
        private TabPage GamePage;
        private TabPage GatewayPage;
        private TabPage WorldPage;
    }
}