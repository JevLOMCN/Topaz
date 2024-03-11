using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Mir_4_Launcher
{
    public partial class CommunityForm : Form
    {
        public CommunityForm()
        {
            InitializeComponent();
            this.Deactivate += CommunityForm_Deactivate;
        }

        private void CommunityForm_Deactivate(object sender, EventArgs e)
        {
            // Close the CommunityForm when it loses focus
            this.Close();
        }

        private void LOMCNBox_MouseHover(object sender, EventArgs e)
        {
            // Change the image when the mouse enters the PictureBox
            LOMCNBox.Image = Properties.Resources.LOMCNButton_Pressed;
        }

        private void LOMCNBox_MouseLeave(object sender, EventArgs e)
        {
            // Change the image when the mouse leaves the PictureBox
            LOMCNBox.Image = Properties.Resources.LOMCNButton;
        }

        private void LOMCNBox_Click(object sender, EventArgs e)
        {
            string link = "https://www.lomcn.net";

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

        private void DiscordBox_Click(object sender, EventArgs e)
        {
            string link = "https://discord.gg/nwNgzqmJXd";

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

        private void DiscordBox_MouseHover(object sender, EventArgs e)
        {
            // Change the image when the mouse enters the PictureBox
            DiscordBox.Image = Properties.Resources.DiscordButton_Pressed;
        }

        private void DiscordBox_MouseLeave(object sender, EventArgs e)
        {
            // Change the image when the mouse leaves the PictureBox
            DiscordBox.Image = Properties.Resources.DiscordButton;
        }

        private void GithubBox_Click(object sender, EventArgs e)
        {
            string link = "https://github.com/JevLOMCN/mir4";

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

        private void GithubBox_MouseHover(object sender, EventArgs e)
        {
            // Change the image when the mouse enters the PictureBox
            GithubBox.Image = Properties.Resources.GithubButton_Pressed;
        }

        private void GithubBox_MouseLeave(object sender, EventArgs e)
        {
            // Change the image when the mouse leaves the PictureBox
            GithubBox.Image = Properties.Resources.GithubButton;
        }
    }
}
