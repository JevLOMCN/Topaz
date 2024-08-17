using System.Runtime.InteropServices;
using System.Text;

namespace Server_Console.Database_Tool
{
    public class Config
    {
        private readonly string _path;
        public static string CurrentLanguage { get; set; } = "ENG";
        public static string AESKey = "0xAD768F68B8795A776100525791F675E15341D565D9AB4B4B74C95F31B03310F3";
        public Config(string path)
        {
            _path = path;
        }
        public static void Initialize()
        {
            string iniFilePath = FileManager.GetFilePath("config.ini");

            if (!File.Exists(iniFilePath))
            {
                var iniData = new Config(iniFilePath);
                iniData.Write("Settings", "Language", CurrentLanguage);
            }
            else
            {
                var iniData = new Config(iniFilePath);
                string language = iniData.Read("Settings", "Language");
                if (string.IsNullOrWhiteSpace(language))
                {
                    iniData.Write("Settings", "Language", CurrentLanguage);
                    return;
                }
                CurrentLanguage = language;
            }
        }

        public static void SaveLanguageSetting()
        {
            string iniFilePath = FileManager.GetFilePath("config.ini");
            var iniData = new Config(iniFilePath);
            iniData.Write("Settings", "Language", CurrentLanguage);
        }

        public void Write(string section, string key, string value)
        {
            WritePrivateProfileString(section, key, value, _path);
        }

        public string Read(string section, string key)
        {
            var retVal = new StringBuilder(255);
            GetPrivateProfileString(section, key, "", retVal, 255, _path);
            return retVal.ToString();
        }

        [DllImport("kernel32.dll", CharSet = CharSet.Auto)]
        private static extern long WritePrivateProfileString(string section, string key, string val, string filePath);

        [DllImport("kernel32.dll", CharSet = CharSet.Auto)]
        private static extern int GetPrivateProfileString(string section, string key, string def, StringBuilder retVal, int size, string filePath);

    }
}