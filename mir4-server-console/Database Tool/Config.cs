using System.Runtime.InteropServices;
using System.Runtime.Serialization.Formatters.Binary;
using System.Security.Cryptography;
using System.Text;
using System.Windows.Forms.Design;

namespace Server_Console.Database_Tool
{
    public class Config
    {
        private readonly string _filePath;
        public static Cursor customCursor;
        public static string CurrentLanguage { get; set; } = "ENG";
        public const string AESKey = "0xAD768F68B8795A776100525791F675E15341D565D9AB4B4B74C95F31B03310F3";
        public static string mapCacheFileName = "maps.dat";
        public static string iconCacheFileName = "icons.dat";
        public static string? worldMapDefaultResource;

        public const int playerDataAutoRefreshInterval = 60;
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

        public static readonly Dictionary<(int, int), string> InfoSubType = new Dictionary<(int, int), string>
        {
            {(1, 0), "WayPoint"},
            {(2, 0), "Portal"},
            {(2, 3), "Monster"},
            {(2, 4), "MonsterStageBoss"},
            {(2, 5), "MonsterFieldBoss"},
            {(3, 0), "None"},
            {(3, 1), "Mining"},
            {(3, 2), "Gathering"},
            {(3, 3), "MiningBlackIron"},
            {(3, 4), "EnergyGathering"},
            {(4, 1), "NpcShop"},
            {(4, 2), "NpcItemMake"},
            {(4, 4), "NpcBeauty"},
            {(4, 8), "NpcWareHouse"},
            {(4, 100), "NpcBlackIronCoin"},
            {(5, 0), "NpcBasic"},
            {(6, 0), "Summon"},
            {(7, 7), "MonsterStageBoss"},
            {(12, 21), "None"},
            {(12, 22), "CoopBoss"},
            {(13, 31), "Sanctum"},
            {(13, 32), "Monolith"},
            {(13, 33), "Altar"},
            {(13, 41), "Altar"}
        };

        public static readonly HashSet<string> SpecialMaps = new HashSet<string>
        {
            // "EStageType::Siege",
            // "EStageType::Sabuk_R1R2",
            // "EStageType::Plunder",
            "EStageType::Magic_Square",
            "EStageType::SecretDungeon",
            "EStageType::BlackDragon",
            // "EStageType::DeadValley",
            // "EStageType::Bido",
        };

        public static readonly Dictionary<int, (string Path, int StringId)> avatarPaths = new Dictionary<int, (string, int)>
        {
            { 1, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_01_Sprite", 3600001) },
            { 2, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_02_Sprite", 3600002) },
            { 3, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_03_Sprite", 3600003) },
            { 4, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_04_Sprite", 3600004) },
            { 5, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_05_Sprite", 3600005) },
            { 6, ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Ingame/Spr_SmallTalk/Cha_06_Sprite", 3600006) }
        };

        public static readonly Dictionary<string, (string Path, float Scale)> miniMapIconPaths = new Dictionary<string, (string, float)>
        {
            { "Unknown", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Ico_MiniMap_008_Sprite", 1) },
            { "None", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_None_Sprite", 1) },
            { "Mining", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Ico_MiniMap_004_C_Sprite", 1) },
            { "MiningBlackIron", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Ico_MiniMap_012_C_Sprite", 1) },
            { "Gathering", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Ico_MiniMap_005_C_Sprite", 1) },
            { "EnergyGathering", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Ico_MiniMap_013_C_Sprite", 1) },
            { "Altar", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Altar_Sprite", zoomMiniMap) },
            { "CoopBoss", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_CoopBoss_Sprite", 1) },
            { "EliteMonster", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Elite_NamedMonster_Sprite", 1) },
            { "MonsterBoss", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_MonsterBoss_Sprite", 1) },
            { "MonsterFieldBoss", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_MonsterFieldBoss_Sprite", zoomMiniMap) },
            { "MonsterStageBoss", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_MonsterStageBoss_Sprite", zoomMiniMap) },
            { "Monster", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_MonsterNamed_Sprite", 1) },
            { "Sanctum", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Guildwar_Sanctum_Sprite", zoomMiniMap) },
            { "Monolith", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_WinTarget_Sprite", 1) },
            { "NpcWareHouse", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Individual_Storage_Sprite", 1) },
            { "NpcBasic", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcBasic_Sprite", 1) },
            { "NpcBeauty", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcBeauty_Sprite", 1) },
            { "NpcBlackIronCoin", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcBlackIronCoin_Sprite", 1) },
            { "NpcExchange", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcExchange_Sprite", 1) },
            { "NpcItemMake", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcItemMake_Sprite", 1) },
            { "NpcQuest", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcQuest_Sprite", 1) },
            { "NpcRelation", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcRelation_Sprite", 1) },
            { "NpcShop", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_NpcShop_Sprite", 1) },
            { "Portal", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Portal_Sprite", 1) },
            { "Portal02", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Portal_02_Sprite", 1) },
            { "PortalDeactive", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Portal_Deactive_Sprite", 1) },
            { "Summon", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_Summon_Sprite", 1) },
            { "WayPoint", ("MirMobile/Content/UI/Atlas_N_Pack/Sprites_N/Spr_Icon/Spr_Symbol/Spr_Symbol_Ico_MiniMap/Mini_WayPointNormal_Sprite", 1) },
        };

        [Serializable]
        public class CacheData
        {
            public Dictionary<int, Bitmap> CachedBitmaps { get; set; }
            public Dictionary<int, Bitmap> CachedMapItems { get; set; }
            public Dictionary<int, List<(RectangleF areaRect, int mapId)>> MapClickAreas { get; set; }
            public Dictionary<int, Bitmap> CachedItemIcons { get; set; }
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

        private static string ComputeFileHash(string filePath)
        {
            using (SHA256 sha256 = SHA256.Create())
            {
                byte[] fileBytes = File.ReadAllBytes(filePath);
                byte[] hashBytes = sha256.ComputeHash(fileBytes);
                return BitConverter.ToString(hashBytes).Replace("-", "").ToLowerInvariant();
            }
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

        public static bool IsNewVersionDetected(string fileName, string hashParam, string cacheFileName)
        {
            string iniFilePath = FileManager.GetFilePath("config.ini");
            var iniData = new Config(iniFilePath);
            string currentHash;
            string cachedHash;
            string cacheFilePath;
            bool isCacheFileExists;

            if (!string.IsNullOrEmpty(Path.GetExtension(fileName)))
                currentHash = ComputeFileHash(fileName);
            else
                currentHash = ComputeDirectoryHash(fileName);

            cachedHash = iniData.Read("Settings", hashParam);
            cacheFilePath = FileManager.GetFilePath(cacheFileName);
            isCacheFileExists = File.Exists(cacheFilePath);

            if (currentHash != cachedHash || !isCacheFileExists)
            {
                iniData.Write("Settings", hashParam, currentHash);
                return true;
            }
            return false;
        }

        public static void SaveCacheData(string cacheFileName, Dictionary<int, Bitmap> cachedBitmaps, Dictionary<int, Bitmap> cachedMapItems, Dictionary<int, List<(RectangleF areaRect, int mapId)>> mapClickAreas)
        {
            try
            {
                string cacheFilePath = FileManager.GetFilePath(cacheFileName);
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
            }
            catch (Exception ex)
            {
                Log($"Error saving cached data: {ex.Message}");
            }
        }

        public static void SaveCacheData(string cacheFileName, Dictionary<int, Bitmap> cachedIconBitmaps)
        {
            try
            {
                string cacheFilePath = FileManager.GetFilePath(cacheFileName);
                using (FileStream fs = new FileStream(cacheFilePath, FileMode.Create))
                {
                    BinaryFormatter formatter = new BinaryFormatter();

                    CacheData cacheData = new CacheData
                    {
                        CachedItemIcons = cachedIconBitmaps,
                    };

                    formatter.Serialize(fs, cacheData);
                }
            }
            catch (Exception ex)
            {
                Log($"Error saving cached data: {ex.Message}");
            }
        }

        public static void LoadCacheData(string cacheFileName)
        {
            string cacheFilePath = FileManager.GetFilePath(cacheFileName);
            if (!File.Exists(cacheFilePath)) return;

            try
            {
                using (FileStream fs = new FileStream(cacheFilePath, FileMode.Open))
                {
                    BinaryFormatter formatter = new BinaryFormatter();
                    var cacheData = (CacheData)formatter.Deserialize(fs);

                    var cacheHandlers = new Dictionary<string, Action<CacheData>>
                    {
                        { 
                            mapCacheFileName, data =>
                            {
                                MapPage.CachedBitmaps = data.CachedBitmaps;
                                MapPage.CachedMapItems = data.CachedMapItems;
                                MapPage.mapClickAreas = data.MapClickAreas;
                            }
                        },
                        { 
                            iconCacheFileName, data =>
                            {
                                ItemPage.CachedItemIcons = data.CachedItemIcons;
                            }
                        }
                    };

                    if (cacheHandlers.TryGetValue(cacheFileName, out var handler))
                        handler(cacheData);
                    else
                        Log($"No handler for cache file: {cacheFileName}");
                }
            }
            catch (Exception ex)
            {
                Log($"Error loading cached data: {ex.Message}");
            }
        }

        public static void ClearCacheData(string cacheFileName)
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
