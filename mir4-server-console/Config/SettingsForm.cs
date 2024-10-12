using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.Config
{
    public partial class SettingsForm : Form
    {
        public SettingsForm()
        {
            InitializeComponent();
        }

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
            string newBackground = "Horn_guy_Main.png";

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
