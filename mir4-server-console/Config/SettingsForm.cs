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
using System.Diagnostics;

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
            CheckApplicationAndServiceStatus("MySQL", MYSQLLabel, "MySQL", @"C:\Program Files\MySQL\MySQL Server", @"SOFTWARE\MySQL AB", MYSqlButton);
            CheckApplicationAndServiceStatus("Memurai", MemuraiLabel, "Memurai", @"C:\Program Files\Memurai", @"SOFTWARE\Memurai", MemuraiButton);
            CheckApplicationAndServiceStatus("CouchbaseServer", CouchbaseLabel, "Couchbase", @"C:\Program Files\Couchbase\Server", @"SOFTWARE\Couchbase", CouchbaseButton);
        }

        private void CheckApplicationAndServiceStatus(string serviceName, Label serviceLabel, string serviceDisplayName, string installPath, string registryKey, PictureBox pictureBox = null)
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
                        if (pictureBox != null) pictureBox.Image = GetServiceRunningImage(serviceDisplayName);
                    }
                    else
                    {
                        serviceLabel.Text = $"{serviceDisplayName}: Not running";
                        if (pictureBox != null) pictureBox.Image = GetServiceNotRunningImage(serviceDisplayName);
                    }
                }
                catch (Exception)
                {
                    serviceLabel.Text = $"{serviceDisplayName}: Not running";
                    if (pictureBox != null) pictureBox.Image = GetServiceNotRunningImage(serviceDisplayName);
                }
            }
            else
            {
                serviceLabel.Text = $"{serviceDisplayName}: Not installed";
                if (pictureBox != null) pictureBox.Image = GetServiceNotInstalledImage(serviceDisplayName);
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

        // Image retrieval helper methods
        private Image GetServiceRunningImage(string serviceDisplayName)
        {
            switch (serviceDisplayName)
            {
                case "MySQL":
                    return Properties.Resources.MYSQL; // MySQL.png
                case "Memurai":
                    return Properties.Resources.Memurai; // Memurai.png
                case "Couchbase":
                    return Properties.Resources.Couchbase; // Couchbase.png
                default:
                    return null;
            }
        }

        private Image GetServiceNotRunningImage(string serviceDisplayName)
        {
            switch (serviceDisplayName)
            {
                case "MySQL":
                    return Properties.Resources.MYSQL1; // MySQL1.png
                case "Memurai":
                    return Properties.Resources.Memurai1; // Memurai1.png
                case "Couchbase":
                    return Properties.Resources.Couchbase1; // Couchbase1.png
                default:
                    return null;
            }
        }

        private Image GetServiceNotInstalledImage(string serviceDisplayName)
        {
            switch (serviceDisplayName)
            {
                case "MySQL":
                    return Properties.Resources.MYSQL2; // MySQL2.png
                case "Memurai":
                    return Properties.Resources.Memurai2; // Memurai2.png
                case "Couchbase":
                    return Properties.Resources.Couchbase2; // Couchbase2.png
                default:
                    return null;
            }
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

        #region Memurai Click/Download/Start/Stop
        private void MemuraiButton_Click(object sender, EventArgs e)
        {
            bool isMemuraiInstalled = IsApplicationInstalled(@"C:\Program Files\Memurai", @"SOFTWARE\Memurai");

            if (!isMemuraiInstalled)
            {
                // Memurai is not installed, open the download page in the default browser
                OpenMemuraiDownloadPage();
            }
            else
            {

            }
        }
        private void OpenMemuraiDownloadPage()
        {
            try
            {
                // Open the Memurai download page in the default browser
                Process.Start(new ProcessStartInfo
                {
                    FileName = "https://www.memurai.com/get-memurai#",
                    UseShellExecute = true
                });
            }
            catch (Exception ex)
            {
                // Handle exceptions (optional)
                MessageBox.Show("Failed to open the browser. " + ex.Message);
            }
        }
        #endregion

        #region MySQL Click/Download/Start/Stop
        private void MYSqlButton_Click(object sender, EventArgs e)
        {
            bool isMySQLInstalled = IsApplicationInstalled(@"C:\Program Files\MySQL\MySQL Server", @"SOFTWARE\MySQL AB");

            if (!isMySQLInstalled)
            {
                // MySQL is not installed, open the MySQL installer download page in the default browser
                OpenMySQLDownloadPage();
            }
            else
            {

            }
        }

        private void OpenMySQLDownloadPage()
        {
            try
            {
                // Open the MySQL installer download page in the default browser
                Process.Start(new ProcessStartInfo
                {
                    FileName = "https://dev.mysql.com/get/Downloads/MySQLInstaller/mysql-installer-community-5.7.44.0.msi",
                    UseShellExecute = true
                });
            }
            catch (Exception ex)
            {
                // Handle exceptions (optional)
                MessageBox.Show("Failed to open the browser. " + ex.Message);
            }
        }
        #endregion

        #region Couchbase Click/Download/Start/Stop
        private void CouchbaseButton_Click(object sender, EventArgs e)
        {
            bool isCouchbaseInstalled = IsApplicationInstalled(@"C:\Program Files\Couchbase\Server", @"SOFTWARE\Couchbase");

            if (!isCouchbaseInstalled)
            {
                // Couchbase is not installed, open the Couchbase installer download page in the default browser
                OpenCouchbaseDownloadPage();
            }
            else
            {

            }
        }

        private void OpenCouchbaseDownloadPage()
        {
            try
            {
                // Open the Couchbase installer download page in the default browser
                Process.Start(new ProcessStartInfo
                {
                    FileName = "https://packages.couchbase.com/releases/7.2.0/couchbase-server-enterprise_7.2.0-windows_amd64.msi",
                    UseShellExecute = true
                });
            }
            catch (Exception ex)
            {
                // Handle exceptions (optional)
                MessageBox.Show("Failed to open the browser. " + ex.Message);
            }
        }
        #endregion
    }
}
