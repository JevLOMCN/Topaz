using System;
using System.Collections.Generic;
using System.Globalization;
using System.IO;
using System.Linq;
using System.Runtime.InteropServices;
using System.Windows.Forms;
using CsvHelper;
using CsvHelper.Configuration;

namespace Server_Console.Database_Tool
{
    public partial class DatabaseTool : Form
    {
        public DatabaseTool()
        {
            InitializeComponent();
            this.Shown += new EventHandler(DatabaseTool_Shown);
        }
        private void DatabaseTool_Shown(object sender, EventArgs e)
        {
            InitializeData();
        }

        #region Initialization Methods

        private async void InitializeData()
        {
            int totalSteps = 6;
            int currentStep = 0;

            InitializeLanguageMenu();
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Run(() => Config.Initialize());
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Run(() => ImageProcessor.Initialize());
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Run(() => FileManager.Initialize());
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Run(() => MapPage.LoadData());
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Run(() => CommandPage.Initialize());
            currentStep++;
            UpdateProgressBar((currentStep * 100) / totalSteps);

            await Task.Delay(1000);

            progressBar.Visible = false;
            statusLabel.Text = "Completed";
        }

        private void InitializeLanguageMenu()
        {
            var languages = new Dictionary<string, string>
            {
                { "TextKr", "한국어" },
                { "CHT", "中文(繁體)" },
                { "CHS", "中文(简体)" },
                { "JPN", "日本語" },
                { "ENG", "English" },
                { "THA", "ภาษาไทย" },
                { "IND", "Bahasa Indonesia" },
                { "VIE", "Tiếng Việt" },
                { "GER", "Deutsch" },
                { "SPA", "Español" },
                { "POR", "Português" },
                { "RUS", "Русский" }
            };

            foreach (var lang in languages)
            {
                var menuItem = new ToolStripMenuItem(lang.Value)
                {
                    Tag = lang.Key
                };
                menuItem.Click += LanguageMenuItem_Click;
                languageMenuItem.DropDownItems.Add(menuItem);
            }
        }

        #endregion

        #region Event Handlers

        private void DatabaseTool_Load(object sender, EventArgs e)
        {
            EnsureDataFolderExists();
            UpdateTabItemSize();
        }

        private void LanguageMenuItem_Click(object sender, EventArgs e)
        {
            if (sender is ToolStripMenuItem menuItem && menuItem.Tag is string langCode)
            {
                if (Config.CurrentLanguage == langCode)
                    return;

                var result = MessageBox.Show("Switching languages requires restarting the application. Would you like to restart now?", "Notice", MessageBoxButtons.YesNo, MessageBoxIcon.Question);
                if (result == DialogResult.Yes)
                {
                    Config.CurrentLanguage = langCode;
                    Config.SaveLanguageSetting();
                    Config.ClearCacheData();
                    this.Hide();
                    var newForm = new DatabaseTool();
                    newForm.Show();
                    this.Close();
                }
            }
        }

        #endregion

        #region Public Methods

        public static void Log(string message)
        {
            var mainForm = Application.OpenForms.OfType<DatabaseTool>().FirstOrDefault();
            if (mainForm != null)
            {
                mainForm.AppendLog(message);
            }
        }

        #endregion

        #region Private Methods
        private void UpdateProgressBar(int progress)
        {
            if (progressBar.InvokeRequired)
            {
                progressBar.Invoke(new Action(() => progressBar.Value = progress));
            }
            else
            {
                progressBar.Value = progress;
            }
        }

        private void AppendLog(string message)
        {
            if (logTextBox.InvokeRequired)
            {
                logTextBox.Invoke(new Action(() => logTextBox.AppendText($"{message}{Environment.NewLine}")));
            }
            else
            {
                logTextBox.AppendText($"{message}{Environment.NewLine}");
            }
        }

        private void EnsureDataFolderExists()
        {
            string dataFolderPath = Path.Combine(Application.StartupPath, "Assets");
            if (!Directory.Exists(dataFolderPath))
            {
                Directory.CreateDirectory(dataFolderPath);
            }
        }

        private void UpdateTabItemSize()
        {
            int tabCount = tabControl1.TabCount;
            if (tabCount > 0)
            {
                int tabWidth = (int)Math.Floor((double)tabControl1.Width / tabCount);
                if (tabControl1.ItemSize.Width != tabWidth)
                {
                    tabControl1.ItemSize = new Size(tabWidth, tabControl1.ItemSize.Height);
                }
            }
        }
        #endregion
    }
}
