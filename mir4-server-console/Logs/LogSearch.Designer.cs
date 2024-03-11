namespace Server_Console.Logs
{
    partial class LogSearch
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(LogSearch));
            LogSearchBox = new TextBox();
            SearchButton = new Button();
            SuspendLayout();
            // 
            // LogSearchBox
            // 
            LogSearchBox.Location = new Point(12, 27);
            LogSearchBox.Name = "LogSearchBox";
            LogSearchBox.Size = new Size(209, 23);
            LogSearchBox.TabIndex = 0;
            LogSearchBox.KeyDown += LogSearchBox_KeyDown;
            // 
            // SearchButton
            // 
            SearchButton.Location = new Point(80, 56);
            SearchButton.Name = "SearchButton";
            SearchButton.Size = new Size(75, 23);
            SearchButton.TabIndex = 7;
            SearchButton.Text = "Search";
            SearchButton.UseVisualStyleBackColor = true;
            SearchButton.Click += SearchButton_Click;
            // 
            // LogSearch
            // 
            AutoScaleDimensions = new SizeF(7F, 15F);
            AutoScaleMode = AutoScaleMode.Font;
            ClientSize = new Size(235, 91);
            Controls.Add(SearchButton);
            Controls.Add(LogSearchBox);
            Icon = (Icon)resources.GetObject("$this.Icon");
            MaximizeBox = false;
            MinimizeBox = false;
            Name = "LogSearch";
            StartPosition = FormStartPosition.CenterScreen;
            Text = "Search";
            ResumeLayout(false);
            PerformLayout();
        }

        #endregion

        private TextBox LogSearchBox;
        private Button SearchButton;
    }
}