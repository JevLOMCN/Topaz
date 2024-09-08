using Server_Console.Properties;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Configuration;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace Server_Console.Database
{
    public partial class DatabaseConfiguration : Form
    {
        string hostname = "Please enter the database hostname or IP";
        string username = "Please enter the database username";
        string password = "Please enter the database password";

        public DatabaseConfiguration()
        {
            InitializeComponent();
        }

        //Exit the form
        private void btn_Close_Click(object sender, EventArgs e)
        {
            //Close the form
            this.Close();
        }

        private void DatabaseConfiguration_Load(object sender, EventArgs e)
        {
            //Clear the connection status label
            lbl_ConnectionStatus.Text = string.Empty;

            //Check if the application has been configured and load the configured settings
            if (Settings.Default.Configured)
            {
                //Load the configured settings into the variables
                hostname = Settings.Default.Hostname;
                username = Settings.Default.Username;
                password = Settings.Default.Password;

                //Set the textboxes to the configured settings
                txt_Hostname.Text = hostname;
                txt_Username.Text = username;
                txt_Password.Text = password;
            }
            else
            {
                //Set the textboxes to the default settings
                txt_Hostname.Text = hostname;
                txt_Username.Text = username;
                txt_Password.Text = password;
            }
        }

        public static string GetConnectionString(string databaseName)
        {
            return $"server={Settings.Default.Hostname};user={Settings.Default.Username};password={Settings.Default.Password};database={databaseName};port=3306;";
        }

        private void btn_TestConnection_Click(object sender, EventArgs e)
        {
            //Test the database connection
            if (DatabaseFunctions.TestDatabaseConnection(txt_Hostname.Text, txt_Username.Text, txt_Password.Text))
            {
                //Set the connection status label to successful if the connection is successful
                lbl_ConnectionStatus.Text = "Connection Successful";
                lbl_ConnectionStatus.ForeColor = Color.Green;

                //Disable the test connection button and enable the save configuration button
                btn_TestConnection.Enabled = false;
                btn_TestConnection.Visible = false;
                btn_SaveConfig.Enabled = true;
                btn_SaveConfig.Visible = true;
            }
            else
            {
                //Set the connection status label to failed if the connection is unsuccessful
                lbl_ConnectionStatus.Text = "Connection Failed";
                lbl_ConnectionStatus.ForeColor = Color.Red;
            }
        }

        //Save the configuration settings
        private void btn_SaveConfig_Click(object sender, EventArgs e)
        {
            //Save the configuration settings
            Settings.Default.Hostname = txt_Hostname.Text;
            Settings.Default.Username = txt_Username.Text;
            Settings.Default.Password = txt_Password.Text;
            Settings.Default.Configured = true;

            //Try to save if successful show message box with ok button and information icon and close the form, else catch error and display it in a message box
            try
            {
                Settings.Default.Save();
                MessageBox.Show("Database Configuration Saved", "Database Configuration", MessageBoxButtons.OK, MessageBoxIcon.Information);

                //output the config settings to debug console
                Debug.WriteLine($"Database Configuration Saved: Hostname: {Settings.Default.Hostname}, Username: {Settings.Default.Username}, Password: {Settings.Default.Password}");

                this.Close();

            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error saving configuration: {ex.Message}", "Database Configuration", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void txt_Hostname_Enter(object sender, EventArgs e)
        {
            if (txt_Hostname.Text == "Please enter the database hostname or IP")
            {
                txt_Hostname.Text = string.Empty;
            }
        }

        private void txt_Username_Enter(object sender, EventArgs e)
        {
            if (txt_Username.Text == "Please enter the database username")
            {
                txt_Username.Text = string.Empty;
            }
        }

        private void txt_Password_Enter(object sender, EventArgs e)
        {
            if (txt_Password.Text == "Please enter the database password")
            {
                txt_Password.Text = string.Empty;
            }
        }
    }
}
