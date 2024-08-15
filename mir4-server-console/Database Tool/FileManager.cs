using System.Collections.Concurrent;
using Newtonsoft.Json.Linq;
using Microsoft.VisualBasic.FileIO;

namespace Server_Console.Database_Tool
{
    public static class FileManager
    {
        private static LogDelegate? LogMethod;
        private static Action<int>? ProgressUpdateMethod;

        private static Dictionary<string, List<string>> NameSidIndex = new Dictionary<string, List<string>>();
        private static Dictionary<string, List<string>> CHSIndex = new Dictionary<string, List<string>>();

        public static void SetLogMethod(LogDelegate logMethod)
        {
            LogMethod = logMethod;
        }

        public static void SetProgressUpdateMethod(Action<int> progressUpdateMethod)
        {
            ProgressUpdateMethod = progressUpdateMethod;
        }

        public static void Log(string message)
        {
            LogMethod?.Invoke(message);
        }

        public static void UpdateProgress(int progress)
        {
            ProgressUpdateMethod?.Invoke(progress);
        }

        public static Dictionary<string, ItemData> ItemMap { get; private set; } = new Dictionary<string, ItemData>();
        public static Dictionary<string, StringTemplateData> StringTemplateMap { get; private set; } = new Dictionary<string, StringTemplateData>();
        public static Dictionary<string, string> IconPathMap { get; private set; } = new Dictionary<string, string>();
        public static ConcurrentDictionary<string, ItemData> CombinedIndex { get; private set; } = new ConcurrentDictionary<string, ItemData>();

        private static void BuildCombinedIndex()
        {
            var sortedItems = ItemMap.Values.OrderBy(item => item.ItemId);
            CombinedIndex.Clear();

            foreach (var item in sortedItems)
            {
                if (item.NameSid != null && StringTemplateMap.TryGetValue(item.NameSid, out var nameTemplateEntry))
                {
                    string chsName = nameTemplateEntry.CHS ?? "Unknown";
                    string key = $"{item.ItemId};{chsName}";
                    CombinedIndex[key] = item;
                }
            }
        }

        public static void LoadData()
        {
            int totalSteps = 4;
            int currentStep = 0;

            LoadItemData();
            currentStep++;
            UpdateProgress((currentStep * 100) / totalSteps);

            LoadIconData();
            currentStep++;
            UpdateProgress((currentStep * 100) / totalSteps);

            LoadStringTemplate();
            currentStep++;
            UpdateProgress((currentStep * 100) / totalSteps);

            BuildCombinedIndex();
            currentStep++;

            UpdateProgress((currentStep * 100) / totalSteps);
        }

        private static void LoadItemData()
        {
            string itemCsvPath = GetFilePath("Assets/CSV/ITEM.csv");
            if (!File.Exists(itemCsvPath))
            {
                Log($"ITEM.csv not found at {itemCsvPath}");
                return;
            }

            ItemMap.Clear();
            int itemCount = 0;

            using (var parser = new TextFieldParser(itemCsvPath))
            {
                parser.TextFieldType = FieldType.Delimited;
                parser.SetDelimiters(",");
                parser.HasFieldsEnclosedInQuotes = true;

                string[] headers = parser.ReadFields();
                if (headers.Length != 52)
                {
                    Log("Warning: Header length mismatch.");
                    return;
                }

                while (!parser.EndOfData)
                {


                    string[] fields = parser.ReadFields();

                    if (fields.Length != headers.Length)
                    {
                        Log("Warning: Skipping line with mismatched field count.");
                        continue;
                    }

                    for (int i = 0; i < fields.Length; i++)
                    {
                        fields[i] = fields[i].Trim('"', ' ');
                    }

                    string rawMeshId = fields[5].Trim();
                    rawMeshId = rawMeshId.Replace("(", "").Replace(")", "");

                    int meshId;
                    if (!int.TryParse(rawMeshId, out meshId))
                    {
                        meshId = 0;
                    }
                    if (meshId > 0)
                    {
                        meshId = -meshId;
                    }

                    var itemData = new ItemData
                    {
                        Name = fields[0],
                        ItemId = fields[1],
                        UseId = fields[2],
                        NameSid = fields[3],
                        NoteSid = fields[4],
                        MeshId = meshId,
                        Icon = fields[6],
                        Level = fields[7],
                        MainType = fields[8],
                        SubType = fields[9],
                        SmeltingType = fields[10],
                        SortOrder = fields[11],
                        ClassId = fields[12],
                        ReqClassLevel = fields[13],
                        Tier = fields[14],
                        Grade = fields[15],
                        PET_Origin = fields[16],
                        SellType = fields[17],
                        SellPrice = fields[18],
                        Stackable = fields[19],
                        CoolTime = fields[20],
                        BuffId = fields[21],
                        ItemOptionType = fields[22],
                        SetGroupId = fields[23],
                        ReinforceMaxLv = fields[24],
                        SmeltingMaxCount = fields[25],
                        JewelUpgradeType = fields[26],
                        JewelUpgradeCount = fields[27],
                        RandomGetInfoId = fields[28],
                        SummonGroupId = fields[29],
                        CastingType = fields[30],
                        CastingTime = fields[31],
                        SummonStageType = fields[32],
                        ItemCasting = fields[33],
                        CollectAniType = fields[34],
                        GetWayId = fields[35],
                        OverlapEquipAble = fields[36],
                        UnsealingType = fields[37],
                        OpenboxRewardType = fields[38],
                        OpenboxRewardId = fields[39],
                        RandomGetCnt = fields[40],
                        Durability = fields[41],
                        Durability_RepairAble = fields[42],
                        TradeType = fields[43],
                        UseTimeType = fields[44],
                        UseEndType_Period = fields[45],
                        UseEndType_Minute = fields[46],
                        Lockable = fields[47],
                        XDracoDelayMinute = fields[48],
                        GachaTap = fields[49],
                        TranceGroup = fields[50],
                        EquipGroup = fields[51]
                    };

                    ItemMap[itemData.ItemId] = itemData;
                    itemCount++;
                }
            }

            Log($"Loaded {itemCount} items.");
        }

        private static void LoadIconData()
        {
            string iconCsvPath = GetFilePath("Assets/CSV/ICON.csv");
            if (!File.Exists(iconCsvPath))
            {
                Log($"ICON.csv not found at {iconCsvPath}");
                return;
            }

            IconPathMap.Clear();
            int iconCount = 0;

            using (var reader = new StreamReader(iconCsvPath))
            {
                var headerLine = reader.ReadLine();
                string line;
                while ((line = reader.ReadLine()) != null)
                {
                    var parts = line.Split(',').Select(p => p.Trim('"', ' ')).ToArray();

                    if (parts.Length >= 4)
                    {
                        string iconId = parts[1];
                        string path = parts[3];
                        IconPathMap[iconId] = $"Assets{path}.uasset";
                        iconCount++;
                    }
                }
            }

            Log($"Loaded {iconCount} icons.");
        }

        private static void LoadStringTemplate()
        {
            string jsonFilePath = GetFilePath("Assets/Json/STRING_TEMPLATE.json");
            if (!File.Exists(jsonFilePath))
            {
                Log($"STRING_TEMPLATE.json not found at {jsonFilePath}");
                return;
            }

            StringTemplateMap.Clear();
            int stringTemplateCount = 0;
            int successCount = 0;
            int failureCount = 0;

            try
            {
                string jsonData = File.ReadAllText(jsonFilePath);
                var stringTemplates = JArray.Parse(jsonData);

                foreach (var template in stringTemplates)
                {
                    var rows = template["Rows"] as JObject;
                    if (rows != null)
                    {
                        foreach (var rowEntry in rows)
                        {
                            var stringId = rowEntry.Key;
                            var chsValue = rowEntry.Value[Config.CurrentLanguage]?.ToString();
                            stringTemplateCount++;

                            if (chsValue != null)
                            {
                                var templateData = new StringTemplateData { StringId = stringId, CHS = chsValue };
                                StringTemplateMap[stringId] = templateData;
                                successCount++;
                            }
                            else
                            {
                                failureCount++;
                            }
                        }
                    }
                }

                Log($"Loaded {stringTemplateCount} string templates. [Success: {successCount}, Failures: {failureCount}]");
            }
            catch (Exception ex)
            {
                Log($"Error loading STRING_TEMPLATE.json: {ex.Message}");
            }
        }

        public static string GetFilePath(string relativePath)
        {
            string fullPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, relativePath);
            return fullPath.Replace('/', '\\');
        }
        public static void BuildIndexes()
        {
            NameSidIndex.Clear();
            CHSIndex.Clear();
            foreach (var item in ItemMap.Values)
            {
                if (item != null && StringTemplateMap.TryGetValue(item.NameSid ?? string.Empty, out var nameTemplate))
                {
                    string chsName = nameTemplate.CHS ?? string.Empty;
                    string nameSid = item.NameSid ?? string.Empty;
                    string itemId = item.ItemId ?? string.Empty;
                    if (!NameSidIndex.ContainsKey(nameSid))
                    {
                        NameSidIndex[nameSid] = new List<string>();
                    }
                    NameSidIndex[nameSid].Add(itemId);

                    if (!CHSIndex.ContainsKey(chsName))
                    {
                        CHSIndex[chsName] = new List<string>();
                    }
                    CHSIndex[chsName].Add(itemId);
                }
            }
        }
        public static List<string> GetMatchingItems(string searchTerm)
        {
            List<string> matches = new List<string>();

            if (string.IsNullOrEmpty(searchTerm))
            {
                matches.AddRange(CombinedIndex.Keys);
            }
            else
            {
                foreach (var entry in CombinedIndex.Keys)
                {
                    if (entry.Contains(searchTerm, StringComparison.OrdinalIgnoreCase))
                    {
                        matches.Add(entry);
                    }
                }
            }

            return matches;
        }
    }

    public class ItemData
    {
        public string? Name { get; set; }
        public string? ItemId { get; set; }
        public string? UseId { get; set; }
        public string? NameSid { get; set; }
        public string? NoteSid { get; set; }
        public int? MeshId { get; set; }
        public string? Icon { get; set; }
        public string? Level { get; set; }
        public string? MainType { get; set; }
        public string? SubType { get; set; }
        public string? SmeltingType { get; set; }
        public string? SortOrder { get; set; }
        public string? ClassId { get; set; }
        public string? ReqClassLevel { get; set; }
        public string? Tier { get; set; }
        public string? Grade { get; set; }
        public string? PET_Origin { get; set; }
        public string? SellType { get; set; }
        public string? SellPrice { get; set; }
        public string? Stackable { get; set; }
        public string? CoolTime { get; set; }
        public string? BuffId { get; set; }
        public string? ItemOptionType { get; set; }
        public string? SetGroupId { get; set; }
        public string? ReinforceMaxLv { get; set; }
        public string? SmeltingMaxCount { get; set; }
        public string? JewelUpgradeType { get; set; }
        public string? JewelUpgradeCount { get; set; }
        public string? RandomGetInfoId { get; set; }
        public string? SummonGroupId { get; set; }
        public string? CastingType { get; set; }
        public string? CastingTime { get; set; }
        public string? SummonStageType { get; set; }
        public string? ItemCasting { get; set; }
        public string? CollectAniType { get; set; }
        public string? GetWayId { get; set; }
        public string? OverlapEquipAble { get; set; }
        public string? UnsealingType { get; set; }
        public string? OpenboxRewardType { get; set; }
        public string? OpenboxRewardId { get; set; }
        public string? RandomGetCnt { get; set; }
        public string? Durability { get; set; }
        public string? Durability_RepairAble { get; set; }
        public string? TradeType { get; set; }
        public string? UseTimeType { get; set; }
        public string? UseEndType_Period { get; set; }
        public string? UseEndType_Minute { get; set; }
        public string? Lockable { get; set; }
        public string? XDracoDelayMinute { get; set; }
        public string? GachaTap { get; set; }
        public string? TranceGroup { get; set; }
        public string? EquipGroup { get; set; }
    }

    public class StringTemplateData
    {
        public string? StringId { get; set; }
        public string? CHS { get; set; }
    }
}
