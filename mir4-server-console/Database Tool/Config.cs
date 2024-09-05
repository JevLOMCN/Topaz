using System;
using System.Collections.Generic;
using System.Drawing;
using System.IO;
using System.Runtime.InteropServices;
using System.Runtime.Serialization.Formatters.Binary;
using System.Security.Cryptography;
using System.Text;

namespace Server_Console.Database_Tool
{
    public class Config
    {
        private readonly string _filePath;
        public static Cursor customCursor;
        public static string CurrentLanguage { get; set; } = "ENG";
        public const string AESKey = "0xAD768F68B8795A776100525791F675E15341D565D9AB4B4B74C95F31B03310F3";
        public static string cacheFileName = "maps.dat";
        public static string nextIconPath = "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Common/Icon_next_Big_Sprite";
        public static string? worldMapDefaultResource;
        public const int worldMapDefaultId = 0;
        public const int worldMapDefaultClassId = 1;
        public const int worldMapDefaultIcon = 900001;
        public const int worldMapMessageId = 1019002;
        public static int worldMapSizeX = 2040;
        public static int worldMapSizeY = 1022;
        public static float offsetWorldMapX = 406;
        public static float offsetWorldMapY = 116;
        public static float offsetTouchWorldMapX = offsetWorldMapX + 40;
        public static float offsetTouchWorldMapY = offsetWorldMapY + 60;
        public static float offsetAreaMapX = 370;
        public static float offsetAreaMapY = 150;
        public static float offsetTouchAreaMapX = offsetAreaMapX + 40;
        public static float offsetTouchAreaMapY = offsetAreaMapY + 30;
        public static float zoomMiniMap = 1.43f;
        public static float offsetMiniMapX = 378;
        public static float offsetMiniMapY = 178;
        public static readonly Dictionary<int, string> avatarPaths = new Dictionary<int, string>
        {
            { 1, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_01_Sprite" },
            { 2, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_02_Sprite" },
            { 3, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_03_Sprite" },
            { 4, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_04_Sprite" },
            { 5, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_05_Sprite" },
            { 6, "MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_06_Sprite" }
        };

        [Serializable]
        public class CacheData
        {
            public Dictionary<int, Bitmap> CachedBitmaps { get; set; }
            public Dictionary<int, Bitmap> CachedMapItems { get; set; }
            public Dictionary<int, List<(RectangleF areaRect, int mapId)>> MapClickAreas { get; set; }
        }

        public static HashSet<string> NeedBoldTextLanguages = new HashSet<string>
        {
            "TextKr",
            "CHS",
            "CHT",
            "JPN",
        };

        private static void Log(string message) => DatabaseTool.Log(message);

        public Config(string path)
        {
            _filePath = path;
        }

        public static void Initialize()
        {
            string iniFilePath = FileManager.GetFilePath("config.ini");
            try
            {
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
                    }
                    else
                    {
                        CurrentLanguage = language;
                    }
                }
            }
            catch (Exception ex)
            {
                Log($"Error initializing configuration: {ex.Message}");
            }
        }

        public static void SaveLanguageSetting()
        {
            try
            {
                string iniFilePath = FileManager.GetFilePath("config.ini");
                var iniData = new Config(iniFilePath);
                iniData.Write("Settings", "Language", CurrentLanguage);
            }
            catch (Exception ex)
            {
                Log($"Error saving language setting: {ex.Message}");
            }
        }

        public void Write(string section, string key, string value)
        {
            try
            {
                WritePrivateProfileString(section, key, value, _filePath);
            }
            catch (Exception ex)
            {
                Log($"Error writing to config file: {ex.Message}");
            }
        }

        public string Read(string section, string key)
        {
            var retVal = new StringBuilder(255);
            try
            {
                GetPrivateProfileString(section, key, "", retVal, retVal.Capacity, _filePath);
            }
            catch (Exception ex)
            {
                Log($"Error reading from config file: {ex.Message}");
            }
            return retVal.ToString();
        }

        public static string ComputeDirectoryHash(string directoryPath)
        {
            using (SHA256 sha256 = SHA256.Create())
            {
                var files = Directory.GetFiles(directoryPath, "*.*", SearchOption.AllDirectories)
                    .OrderBy(p => p).ToList();

                foreach (string file in files)
                {
                    byte[] fileBytes = File.ReadAllBytes(file);
                    sha256.TransformBlock(fileBytes, 0, fileBytes.Length, fileBytes, 0);
                }

                sha256.TransformFinalBlock(new byte[0], 0, 0);
                return BitConverter.ToString(sha256.Hash).Replace("-", "").ToLowerInvariant();
            }
        }

        public static bool IsNewVersionDetected()
        {
            string iniFilePath = FileManager.GetFilePath("config.ini");
            string assetsDirectoryPath = "Assets/Paks";
            string currentHash = ComputeDirectoryHash(assetsDirectoryPath);

            var iniData = new Config(iniFilePath);
            string cachedHash = iniData.Read("Settings", "PaksHash");

            string cacheFilePath = FileManager.GetFilePath(cacheFileName);
            bool isCacheFileExists = File.Exists(cacheFilePath);

            if (currentHash != cachedHash || !isCacheFileExists)
            {
                iniData.Write("Settings", "PaksHash", currentHash);
                return true;
            }

            return false;
        }

        public static void SaveCacheData(Dictionary<int, Bitmap> cachedBitmaps, Dictionary<int, Bitmap> cachedMapItems, Dictionary<int, List<(RectangleF areaRect, int mapId)>> mapClickAreas)
        {
            try
            {
                string cacheFilePath = FileManager.GetFilePath(Config.cacheFileName);
                using (FileStream fs = new FileStream(cacheFilePath, FileMode.Create))
                {
                    BinaryFormatter formatter = new BinaryFormatter();

                    CacheData cacheData = new CacheData
                    {
                        CachedBitmaps = cachedBitmaps,
                        CachedMapItems = cachedMapItems,
                        MapClickAreas = mapClickAreas
                    };

                    formatter.Serialize(fs, cacheData);
                }
                Log("Cached data saved.");
            }
            catch (Exception ex)
            {
                Log($"Error saving cached data: {ex.Message}");
            }
        }

        public static void LoadCacheData()
        {
            try
            {
                string cacheFilePath = FileManager.GetFilePath(Config.cacheFileName);
                if (File.Exists(cacheFilePath))
                {
                    using (FileStream fs = new FileStream(cacheFilePath, FileMode.Open))
                    {
                        BinaryFormatter formatter = new BinaryFormatter();

                        var cacheData = (CacheData)formatter.Deserialize(fs);
                        MapPage.CachedBitmaps = cacheData.CachedBitmaps;
                        MapPage.CachedMapItems = cacheData.CachedMapItems;
                        MapPage.mapClickAreas = cacheData.MapClickAreas;
                    }
                    Log("Loaded cached data.");
                }
                else
                {
                    Log("Cache file not found.");
                }
            }
            catch (Exception ex)
            {
                Log($"Error loading cached data: {ex.Message}");
            }
        }

        public static void ClearCacheData()
        {
            try
            {
                string cacheFilePath = FileManager.GetFilePath(cacheFileName);

                if (File.Exists(cacheFilePath))
                {
                    File.Delete(cacheFilePath);
                    Log("Cached data deleted.");
                }
            }
            catch (Exception ex)
            {
                Log($"Error deleting cache file: {ex.Message}");
            }
        }

        public static void LoadCustomCursor()
        {
            Bitmap? iconCursor = ImageProcessor.GetIconFromUE4("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_Hud/Icon_Cursor_01_Sprite");
            Bitmap cursorBitmap = new Bitmap(iconCursor, new Size(32, 32));
            IntPtr iconPtr = CreateCursor(cursorBitmap, 0, 0);
            customCursor = new Cursor(iconPtr);
        }

        public static IntPtr CreateCursor(Bitmap bitmap, int xHotspot, int yHotspot)
        {
            ICONINFO iconInfo = new ICONINFO();
            iconInfo.fIcon = false;
            iconInfo.xHotspot = xHotspot;
            iconInfo.yHotspot = yHotspot;
            iconInfo.hbmMask = bitmap.GetHbitmap();
            iconInfo.hbmColor = bitmap.GetHbitmap();

            return CreateIconIndirect(ref iconInfo);
        }

        [DllImport("user32.dll", CharSet = CharSet.Auto)]
        private static extern IntPtr CreateIconIndirect(ref ICONINFO iconInfo);

        [StructLayout(LayoutKind.Sequential)]
        private struct ICONINFO
        {
            public bool fIcon;
            public int xHotspot;
            public int yHotspot;
            public IntPtr hbmMask;
            public IntPtr hbmColor;
        }

        [DllImport("kernel32.dll", CharSet = CharSet.Auto)]
        private static extern long WritePrivateProfileString(string section, string key, string val, string filePath);

        [DllImport("kernel32.dll", CharSet = CharSet.Auto)]
        private static extern int GetPrivateProfileString(string section, string key, string def, StringBuilder retVal, int size, string filePath);
    }
}
