using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.ServiceProcess;
using Microsoft.Win32;

namespace Server_Console.Config
{
    public partial class SettingsForm : Form
    {
        public SettingsForm()
        {
            InitializeComponent();
            DependencyCheck();
        }

        #region DependencyCheck
        public void DependencyCheck()
        {
            CheckApplicationAndServiceStatus("MySQL", MYSQLLabel, "MySQL", @"C:\Program Files\MySQL\MySQL Server", @"SOFTWARE\MySQL AB");
            CheckApplicationAndServiceStatus("Memurai", MemuraiLabel, "Memurai", @"C:\Program Files\Memurai", @"SOFTWARE\Memurai");
        }

        private void CheckApplicationAndServiceStatus(string serviceName, Label serviceLabel, string serviceDisplayName, string installPath, string registryKey)
        {
            bool isInstalled = IsApplicationInstalled(installPath, registryKey);

            if (isInstalled)
            {
                try
                {
                    ServiceController sc = new ServiceController(serviceName);
                    if (sc.Status == ServiceControllerStatus.Running)
                    {
                        serviceLabel.Text = $"{serviceDisplayName}: Running";
                    }
                    else
                    {
                        serviceLabel.Text = $"{serviceDisplayName}: Not running";
                    }
                }
                catch (Exception)
                {
                    serviceLabel.Text = $"{serviceDisplayName}: Not running";
                }
            }
            else
            {
                serviceLabel.Text = $"{serviceDisplayName}: Not installed";
            }
        }

        private bool IsApplicationInstalled(string installPath, string registryKey)
        {
            // Check if the application exists in the Program Files directory
            if (Directory.Exists(installPath))
            {
                return true;
            }

            // Check if the application is registered in the Windows Registry
            using (RegistryKey key = Registry.LocalMachine.OpenSubKey(registryKey))
            {
                if (key != null)
                {
                    return true;
                }
            }

            return false;
        }
        #endregion

        #region Background select
        private void BackgroundBeacon_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Beacon_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundDesert_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Desert_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundForest_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Forest_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundGroup_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Group_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundGuy_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Guy_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundHorn_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Horn_Guy_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundHorse_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Horse_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundIce_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Ice_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundRun_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Run_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundWoman_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Woman_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundWater_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Water_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }

        private void BackgroundWitch_Click(object sender, EventArgs e)
        {
            string settingsFile = Path.Combine(Application.StartupPath, "Settings.ini");
            string newBackground = "Witch_Main.png";

            // Update or create Settings.ini with the new background setting
            using (StreamWriter writer = new StreamWriter(settingsFile))
            {
                writer.WriteLine("Background = " + newBackground);
            }

            // Show a message box to inform the user of the restart
            MessageBox.Show("Background changed, Server console must restart", "Restart Required", MessageBoxButtons.OK, MessageBoxIcon.Information);

            // Restart the application
            Application.Restart();
            Environment.Exit(0); // Ensure the current instance is fully closed
        }
        #endregion
    }
}
