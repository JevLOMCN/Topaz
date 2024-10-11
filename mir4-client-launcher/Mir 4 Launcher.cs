using System;
using System.Diagnostics;
using System.Drawing;
using System.IO;
using System.Windows.Forms;

namespace Mir_4_Launcher
{
    public partial class Launcher : Form
    {
        private CommunityForm CommunityForm;
        private System.Windows.Forms.Timer processCheckTimer;
        private bool isDragging = false;
        private Point lastCursor;
        private Point lastForm;

        public Launcher()
        {
            InitializeComponent();
            UpdateVersionLabel();
            InitializeProcessCheckTimer();
        }

        #region Close/Minimize Buttons
        private void CloseImage_Click(object sender, EventArgs e)
        {
            this.Close();
        }

        private void MinimizeImage_Click(object sender, EventArgs e)
        {
            this.WindowState = FormWindowState.Minimized;
        }
        #endregion
        
        #region Game Start Buttons
        private void GameStartButton1_Click(object sender, EventArgs e)
        {
            string currentDirectory = Environment.CurrentDirectory;
            string mirMobileFolderPath = Path.Combine(currentDirectory, "Client");
            string batFilePath = Path.Combine(mirMobileFolderPath, "MirMobile_DirectX.bat");

            if (File.Exists(batFilePath))
            {
                ProcessStartInfo psi = new ProcessStartInfo(batFilePath)
                {
                    WorkingDirectory = mirMobileFolderPath,
                    WindowStyle = ProcessWindowStyle.Hidden
                };

                Process.Start(psi);
            }
            else
            {
                MessageBox.Show("MirMobile_DirectX missing from MirMobile directory.");
            }
        }
        private void GameStartButton2_Click(object sender, EventArgs e)
        {
            string currentDirectory = Environment.CurrentDirectory;
            string mirMobileFolderPath = Path.Combine(currentDirectory, "Client");
            string batFilePath = Path.Combine(mirMobileFolderPath, "MirMobile_DirectX2.bat");

            if (File.Exists(batFilePath))
            {
                ProcessStartInfo psi = new ProcessStartInfo(batFilePath)
                {
                    WorkingDirectory = mirMobileFolderPath,
                    WindowStyle = ProcessWindowStyle.Hidden
                };

                Process.Start(psi);
            }
            else
            {
                MessageBox.Show("MirMobile_DirectX missing from MirMobile directory.");
            }
        }
        #endregion

        #region Moveable Form
        private void Form1_MouseDown(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                isDragging = true;
                lastCursor = Cursor.Position;
                lastForm = this.Location;
            }
        }

        private void Form1_MouseMove(object sender, MouseEventArgs e)
        {
            if (isDragging)
            {
                Point delta = Point.Subtract(Cursor.Position, new Size(lastCursor));
                this.Location = Point.Add(lastForm, new Size(delta));
            }
        }

        private void Form1_MouseUp(object sender, MouseEventArgs e)
        {
            isDragging = false;
        }
        #endregion

        #region Community Form
        private void CommunityImage_Click(object sender, EventArgs e)
        {
            // Create an instance of CommunityForm
            CommunityForm communityForm = new CommunityForm();

            // Calculate the location relative to the main form
            int offsetX = this.Location.X + 690; // Adjust these values as needed
            int offsetY = this.Location.Y + 40; // Adjust these values as needed
            communityForm.Location = new Point(offsetX, offsetY);

            // Show the form
            communityForm.Show();
        }

        private void CommunityLabel_Click(object sender, EventArgs e)
        {
            // Create an instance of CommunityForm
            CommunityForm communityForm = new CommunityForm();

            // Calculate the location relative to the main form
            int offsetX = this.Location.X + 690; // Adjust these values as needed
            int offsetY = this.Location.Y + 40; // Adjust these values as needed
            communityForm.Location = new Point(offsetX, offsetY);

            // Show the form
            communityForm.Show();
        }
        #endregion

        #region Version Checking
        private void UpdateVersionLabel()
        {
            string buildVersionFilePath = Path.Combine("Client", "MirMobile", "Content", "additional", "buildversion.ini");

            // Check if the build version file exists
            if (File.Exists(buildVersionFilePath))
            {
                // Read the contents of the build version file
                string[] lines = File.ReadAllLines(buildVersionFilePath);

                // Find the line containing BuildRevision
                foreach (string line in lines)
                {
                    if (line.StartsWith("BuildRevision"))
                    {
                        // Extract the BuildRevision number
                        string[] parts = line.Split('=');
                        if (parts.Length == 2)
                        {
                            string buildRevision = parts[1].Trim();
                            VersionLabel.Text = "ver " + buildRevision;

                        }
                    }
                }
            }
        }
        #endregion

        #region Settings Form
        private void SettingsLabel_Click(object sender, EventArgs e)
        {
            // Open the SettingsForm when SettingsLabel is clicked
            SettingsForm settingsForm = new SettingsForm();
            settingsForm.ShowDialog();
        }
        private void SettingsBox_Click(object sender, EventArgs e)
        {
            // Open the SettingsForm when SettingsLabel is clicked
            SettingsForm settingsForm = new SettingsForm();
            settingsForm.ShowDialog();
        }
        #endregion

        #region Info Form
        private void InfoImage_Click(object sender, EventArgs e)
        {
            // Open the InfoForm when Info Image is clicked
            InfoForm InfoForm = new InfoForm();
            InfoForm.ShowDialog();
        }
        #endregion

        #region Process Checking
        private void InitializeProcessCheckTimer()
        {
            processCheckTimer = new System.Windows.Forms.Timer();
            processCheckTimer.Interval = 5000;
            processCheckTimer.Tick += ProcessCheckTimer_Tick;
            processCheckTimer.Start();
        }

        private void ProcessCheckTimer_Tick(object sender, EventArgs e)
        {
            if (IsProcessRunning("MirMobile-Win64-Shipping"))
            {
                GameStartButton1.Image = Properties.Resources.StartGreyed;
                GameStartButton1.Enabled = false;
            }
            else
            {
                GameStartButton1.Image = Properties.Resources.Start;
                GameStartButton1.Enabled = true;
            }

            if (IsProcessRunning("MirMobile-Win64-Shipping2"))
            {
                GameStartButton2.Image = Properties.Resources.Start2Greyed;
                GameStartButton2.Enabled = false;
            }
            else
            {
                GameStartButton2.Image = Properties.Resources.Start2;
                GameStartButton2.Enabled = true;
            }
        }

        private bool IsProcessRunning(string processName)
        {
            Process[] processes = Process.GetProcessesByName(processName);
            return processes.Length > 0;
        }
        #endregion

        #region Copyright Label
        private void LOMCNCoLabel_Click(object sender, EventArgs e)
        {
            if (LOMCNCoLabel.Text == "© Topaz Co., LTD. All rights reserved.") LOMCNCoLabel.Text = "Designed by Jev";
            else LOMCNCoLabel.Text = "© Topaz Co., LTD. All rights reserved.";
        }
        #endregion

        #region Topaz Mir 4 Label
        private void TOPAZLABEL_Click(object sender, EventArgs e)
        {
            string link = "https://www.lomcn.net/";

            try
            {
                Process.Start(new ProcessStartInfo
                {
                    FileName = link,
                    UseShellExecute = true
                });
            }
            catch (Exception ex)
            {
                MessageBox.Show("Error: " + ex.Message, "Error", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
        #endregion
    }
}