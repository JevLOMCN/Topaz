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
    public partial class ConfigForm : Form
    {
        private Dictionary<TabPage, Size> originalTabSizes;
        public ConfigForm()
        {
            InitializeComponent();
            SetTabSize();
        }

        #region Page Resize
        private void SetTabSize()
        {
            originalTabSizes = new Dictionary<TabPage, Size>();

            //Set the size for each Page
            SetTabPageSize(ChattingPage, 555, 566);
            SetTabPageSize(FrontPage, 555, 715);
            SetTabPageSize(GamePage, 555, 475);
            SetTabPageSize(GatewayPage, 555, 540);
            SetTabPageSize(WorldPage, 555, 715);
        }

        private void SetTabPageSize(TabPage tabPage, int width, int height)
        {
            // Set size for each page
            tabPage.Size = new Size(width, height);

            // Add the tab page and its size to the dictionary
            originalTabSizes.Add(tabPage, tabPage.Size);
        }
        private void ConfigTabs_SelectedIndexChanged(object sender, EventArgs e)
        {
            // Get the selected tab page
            TabPage selectedTabPage = ConfigTabs.SelectedTab;

            // Resize the form based on the size of the selected tab page
            this.Size = new Size(originalTabSizes[selectedTabPage].Width + this.Padding.Horizontal,
                                 originalTabSizes[selectedTabPage].Height + this.Padding.Vertical);
        }
        #endregion
    }
}