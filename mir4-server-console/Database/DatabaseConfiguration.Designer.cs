namespace Server_Console.Database
{
    partial class DatabaseConfiguration
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(DatabaseConfiguration));
            gb_Configuration = new GroupBox();
            btn_TestConnection = new Button();
            btn_SaveConfig = new Button();
            lbl_Password = new Label();
            lbl_Username = new Label();
            lbl_Hostname = new Label();
            txt_Password = new TextBox();
            txt_Username = new TextBox();
            txt_Hostname = new TextBox();
            lbl_ConnectionStatus = new Label();
            gb_Configuration.SuspendLayout();
            SuspendLayout();
            // 
            // gb_Configuration
            // 
            gb_Configuration.Controls.Add(lbl_ConnectionStatus);
            gb_Configuration.Controls.Add(btn_TestConnection);
            gb_Configuration.Controls.Add(btn_SaveConfig);
            gb_Configuration.Controls.Add(lbl_Password);
            gb_Configuration.Controls.Add(lbl_Username);
            gb_Configuration.Controls.Add(lbl_Hostname);
            gb_Configuration.Controls.Add(txt_Password);
            gb_Configuration.Controls.Add(txt_Username);
            gb_Configuration.Controls.Add(txt_Hostname);
            gb_Configuration.Location = new Point(12, 12);
            gb_Configuration.Name = "gb_Configuration";
            gb_Configuration.Size = new Size(320, 140);
            gb_Configuration.TabIndex = 0;
            gb_Configuration.TabStop = false;
            gb_Configuration.Text = "Configuration";
            // 
            // btn_TestConnection
            // 
            btn_TestConnection.Location = new Point(203, 109);
            btn_TestConnection.Name = "btn_TestConnection";
            btn_TestConnection.Size = new Size(111, 23);
            btn_TestConnection.TabIndex = 3;
            btn_TestConnection.Text = "Test Connection";
            btn_TestConnection.UseVisualStyleBackColor = true;
            btn_TestConnection.Click += btn_TestConnection_Click;
            // 
            // btn_SaveConfig
            // 
            btn_SaveConfig.Enabled = false;
            btn_SaveConfig.Location = new Point(239, 109);
            btn_SaveConfig.Name = "btn_SaveConfig";
            btn_SaveConfig.Size = new Size(75, 23);
            btn_SaveConfig.TabIndex = 4;
            btn_SaveConfig.Text = "Save";
            btn_SaveConfig.UseVisualStyleBackColor = true;
            btn_SaveConfig.Visible = false;
            btn_SaveConfig.Click += btn_SaveConfig_Click;
            // 
            // lbl_Password
            // 
            lbl_Password.AutoSize = true;
            lbl_Password.Location = new Point(6, 83);
            lbl_Password.Name = "lbl_Password";
            lbl_Password.Size = new Size(63, 15);
            lbl_Password.TabIndex = 5;
            lbl_Password.Text = "Password :";
            // 
            // lbl_Username
            // 
            lbl_Username.AutoSize = true;
            lbl_Username.Location = new Point(6, 54);
            lbl_Username.Name = "lbl_Username";
            lbl_Username.Size = new Size(66, 15);
            lbl_Username.TabIndex = 4;
            lbl_Username.Text = "Username :";
            // 
            // lbl_Hostname
            // 
            lbl_Hostname.AutoSize = true;
            lbl_Hostname.Location = new Point(6, 25);
            lbl_Hostname.Name = "lbl_Hostname";
            lbl_Hostname.Size = new Size(68, 15);
            lbl_Hostname.TabIndex = 3;
            lbl_Hostname.Text = "Hostname :";
            // 
            // txt_Password
            // 
            txt_Password.Location = new Point(80, 80);
            txt_Password.Name = "txt_Password";
            txt_Password.Size = new Size(234, 23);
            txt_Password.TabIndex = 2;
            txt_Password.Enter += txt_Password_Enter;
            // 
            // txt_Username
            // 
            txt_Username.Location = new Point(80, 51);
            txt_Username.Name = "txt_Username";
            txt_Username.Size = new Size(234, 23);
            txt_Username.TabIndex = 1;
            txt_Username.Enter += txt_Username_Enter;
            // 
            // txt_Hostname
            // 
            txt_Hostname.Location = new Point(80, 22);
            txt_Hostname.Name = "txt_Hostname";
            txt_Hostname.Size = new Size(234, 23);
            txt_Hostname.TabIndex = 0;
            txt_Hostname.Enter += txt_Hostname_Enter;
            // 
            // lbl_ConnectionStatus
            // 
            lbl_ConnectionStatus.AutoSize = true;
            lbl_ConnectionStatus.Location = new Point(6, 113);
            lbl_ConnectionStatus.Name = "lbl_ConnectionStatus";
            lbl_ConnectionStatus.Size = new Size(119, 15);
            lbl_ConnectionStatus.TabIndex = 8;
            lbl_ConnectionStatus.Text = "lbl_ConnectionStatus";
            // 
            // DatabaseConfiguration
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(344, 165);
            Controls.Add(gb_Configuration);
            FormBorderStyle = FormBorderStyle.FixedDialog;
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MinimizeBox = false;
            Name = "DatabaseConfiguration";
            StartPosition = FormStartPosition.CenterParent;
            Text = "Database Configuration";
            Load += DatabaseConfiguration_Load;
            gb_Configuration.ResumeLayout(false);
            gb_Configuration.PerformLayout();
            ResumeLayout(false);
        }

        #endregion

        private GroupBox gb_Configuration;
        private TextBox txt_Password;
        private TextBox txt_Username;
        private TextBox txt_Hostname;
        private Label lbl_Hostname;
        private Button btn_TestConnection;
        private Button btn_SaveConfig;
        private Label lbl_Password;
        private Label lbl_Username;
        private Label lbl_ConnectionStatus;
    }
}