using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Drawing.Interop;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using static System.Windows.Forms.VisualStyles.VisualStyleElement;

namespace Server_Console.Logs
{
    public partial class LogSearch : Form
    {
        private LogForm logForm = new LogForm();

        public LogSearch(LogForm logForm)
        {
            InitializeComponent();
        }
        
        private void SearchButton_Click(object sender, EventArgs e)
        {

        }

        private void LogSearchBox_KeyDown(object sender, KeyEventArgs e)
        {

        }
    }
}
