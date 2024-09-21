using System.Collections.Concurrent;
using System.Globalization;
using CsvHelper.Configuration;
using CsvHelper;
using Newtonsoft.Json.Linq;
using System.Text.RegularExpressions;
using System.Reflection;
using Newtonsoft.Json.Converters;
using Newtonsoft.Json;
using System.Dynamic;
using Org.BouncyCastle.Asn1.Pkcs;
using static System.Runtime.InteropServices.JavaScript.JSType;
using Server_Console.Database;
using System.Data;

namespace Server_Console.Database_Tool
{
    public static class FileManager
    {
        public static Dictionary<int, ItemData> ItemMap { get; private set; } = new Dictionary<int, ItemData>();
        public static Dictionary<int, MoneyData> MoneyMap { get; private set; } = new Dictionary<int, MoneyData>();
        public static Dictionary<int, StringTemplateData> StringTemplateMap { get; private set; } = new Dictionary<int, StringTemplateData>();
        public static Dictionary<int, StringMessageData> StringMessageMap { get; private set; } = new Dictionary<int, StringMessageData>();
        public static Dictionary<int, string> IconPathMap { get; private set; } = new Dictionary<int, string>();
        public static Dictionary<int, MapWorldData> MapWorldList { get; private set; } = new Dictionary<int, MapWorldData>();
        public static Dictionary<int, List<MapAreaData>> MapAreaList { get; private set; } = new Dictionary<int, List<MapAreaData>>();
        public static Dictionary<int, MapAreaData> MapGroupAreaList { get; private set; } = new Dictionary<int, MapAreaData>();
        public static Dictionary<int, List<MapMiniData>> MapMiniList { get; private set; } = new Dictionary<int, List<MapMiniData>>();
        public static Dictionary<int, MapMiniData> MapStageMiniList { get; private set; } = new Dictionary<int, MapMiniData>();
        public static Dictionary<int, MapStageData> MapStageList { get; private set; } = new Dictionary<int, MapStageData>();
        public static Dictionary<int, MapStageSectorData> MapStageSectorList { get; private set; } = new Dictionary<int, MapStageSectorData>();
        public static Dictionary<int, List<MapMiniInfoData>> MapMiniInfoList { get; private set; } = new Dictionary<int, List<MapMiniInfoData>>();
        public static Dictionary<int, List<MapSectorGradeData>> MapSectorGradeList { get; private set; } = new Dictionary<int, List<MapSectorGradeData>>();
        public static Dictionary<int, ItemViewerData> ItemViewerList { get; private set; } = new Dictionary<int, ItemViewerData>();
        public static Dictionary<int, MapStageData> SpecialMapList { get; private set; } = new Dictionary<int, MapStageData>();
        public static Dictionary<int, List<PlayerData>> PlayerList { get; private set; } = new Dictionary<int, List<PlayerData>>();
        public static Dictionary<int, List<PlayerData>> OnlinePlayerList { get; private set; } = new Dictionary<int, List<PlayerData>>();

        public static Dictionary<int, dynamic> NpcList = new Dictionary<int, dynamic>();
        private static void Log(string message) => DatabaseTool.Log(message);

        public static async Task PreInitialize()
        {
            LoadStringTemplate();
            LoadStringMessage();
        }

        public static void Initialize()
        {
            LoadItemData();
            LoadMoneyData();
            LoadIconData();
            LoadWorldMapData();
            LoadAreaMapData();
            LoadMiniMapData();
            LoadStageMapData();
            LoadStageSectorData();
            LoadItemViewerData();
            LoadPlayerData();
        }

        public static void LoadItemData()
        {
            var itemDataList = LoadJsonData<ItemData>("ITEM.json");

            if (!itemDataList.Any())
            {
                Log("Failed to LoadItemData");
                return;
            }

            ItemMap.Clear();

            var formattedItemMap = new Dictionary<int, ItemData>();

            foreach (var data in itemDataList)
            {
                ItemMap[data.ItemID] = data;
            }

            ItemPage.LoadItemData();

            Log($"Loaded {ItemMap.Count} items.");
        }

        public static void LoadMoneyData()
        {
            var moneyDataList = LoadJsonData<MoneyData>("MONEY.json");

            if (!moneyDataList.Any())
            {
                Log("Failed to LoadMoneyData");
                return;
            }

            MoneyMap.Clear();

            foreach (var data in moneyDataList)
            {
                MoneyMap[data.MoneyId] = data;
            }

            Log($"Loaded {MoneyMap.Count} moneys.");
        }

        private static void LoadIconData()
        {
            var iconDataList = LoadJsonData<IconData>("ICON.json");

            if (!iconDataList.Any())
            {
                Log("Failed to LoadIconData");
                return;
            }

            IconPathMap.Clear();

            foreach (var data in iconDataList)
            {
                IconPathMap[data.IconId] = data.Path ?? string.Empty;
            }

            Log($"Loaded {IconPathMap.Count} icons.");
        }

        private static void LoadWorldMapData()
        {
            var mapWorldDataList = LoadJsonData<MapWorldData>("MAPUI_WORLD.json");

            if (!mapWorldDataList.Any())
            {
                Log("Failed to LoadWorldMapData");
                return;
            }

            MapWorldList.Clear();

            foreach (var data in mapWorldDataList)
            {
                MapWorldList[data.AreaId] = data;
            }

            Log($"Loaded {MapWorldList.Count} world maps.");
        }

        private static void LoadAreaMapData()
        {
            var mapAreaDataList = LoadJsonData<MapAreaData>("MAPUI_AREA.json");

            if (mapAreaDataList == null || !mapAreaDataList.Any())
            {
                Log("Failed to LoadAreaMapData");
                return;
            }

            MapAreaList.Clear();

            foreach (var mapData in mapAreaDataList)
            {
                var areaId = mapData.AreaId;
                if (!MapAreaList.ContainsKey(areaId))
                    MapAreaList[areaId] = new List<MapAreaData>();

                MapAreaList[areaId].Add(mapData);
                MapGroupAreaList[mapData.MiniGroupId] = mapData;
            }

            Log($"Loaded {MapAreaList.Count} area maps.");
        }

        private static void LoadMiniMapData()
        {
            var mapMiniDataList = LoadJsonData<MapMiniData>("MAPUI_MINI.json");

            if (mapMiniDataList == null || !mapMiniDataList.Any())
            {
                Log("Failed to LoadMiniMapData");
                return;
            }

            MapMiniList.Clear();

            foreach (var mapData in mapMiniDataList)
            {
                var miniGroupId = mapData.MiniGroupId;
                if (!MapMiniList.ContainsKey(miniGroupId))
                {
                    MapMiniList[miniGroupId] = new List<MapMiniData>();
                }

                MapMiniList[miniGroupId].Add(mapData);
                MapStageMiniList[mapData.MiniStageId] = mapData;
            }

            Log($"Loaded {MapMiniList.Count} mini maps.");
        }

        private static void LoadStageMapData()
        {
            var mapStageDataList = LoadJsonData<MapStageData>("STAGE.json");

            if (mapStageDataList == null || !mapStageDataList.Any())
            {
                Log("Failed to LoadStageMapData");
                return;
            }

            MapStageList.Clear();
            MapMiniInfoList.Clear();

            foreach (var mapData in mapStageDataList)
            {
                var stageId = mapData.StageID;
                if (Config.SpecialMaps.Contains(mapData.StageType ?? string.Empty))
                    SpecialMapList[stageId] = mapData;

                MapStageList[stageId] = mapData;
                LoadMiniMapInfo(mapData.MapMiniInfo, stageId);
                LoadMapSectorGrade(mapData.SectorGrade, stageId);

            }

            MapPage.LoadMapData();

            Log($"Loaded {MapStageList.Count} stage maps.");
        }

        private static void LoadStageSectorData()
        {
            var mapStageSectorList = LoadJsonData<MapStageSectorData>("STAGE_SECTOR.json");

            if (mapStageSectorList == null || !mapStageSectorList.Any())
            {
                Log("Failed to LoadStageSectorData");
                return;
            }

            MapStageSectorList.Clear();

            foreach (var data in mapStageSectorList)
            {
                var sectorId = data.SectorID;
                MapStageSectorList[sectorId] = data;
            }

            Log($"Loaded {MapStageSectorList.Count} stage sector data.");
        }

        private static void LoadMiniMapInfo(string? path, int stageId)
        {
            if (path == "0")
                return;

            var miniMapInfoDataList = LoadJsonData<MapMiniInfoData>($"MapMini/{path}");

            if (miniMapInfoDataList == null || !miniMapInfoDataList.Any())
                return;

            MapMiniInfoList[stageId] = new List<MapMiniInfoData>(miniMapInfoDataList);
        }

        private static void LoadMapSectorGrade(string? path, int stageId)
        {
            if (path == "0")
                return;

            var miniMapSectorList = LoadJsonData<MapSectorGradeData>($"SectorGrade/{path}");

            if (miniMapSectorList == null || !miniMapSectorList.Any())
                return;

            MapSectorGradeList[stageId] = new List<MapSectorGradeData>(miniMapSectorList);
        }

        private static void LoadItemViewerData()
        {
            var itemViewerDataList = LoadJsonData<ItemViewerData>("ITEM_VIEWER.json");

            if (itemViewerDataList == null || !itemViewerDataList.Any())
            {
                Log("Failed to LoadItemViewerData");
                return;
            }

            ItemViewerList.Clear();

            foreach (var itemViewerData in itemViewerDataList)
            {
                var stageId = itemViewerData.StageId;
                ItemViewerList[stageId] = itemViewerData;
            }

            Log($"Loaded {ItemViewerList.Count} item viewer data.");
        }

        public static void LoadPlayerData()
        {
            try
            {
                string query = "SELECT CharacterUID, CharacterName, Class, Lev, StageIdx, PositionX, PositionY, LoginTime, LogoutTime FROM character_tb;";
                DataTable playerDataTable = DatabaseForm.ExecuteQuery(query, "mm_game_db_release");

                if (playerDataTable.Rows.Count == 0)
                {
                    Log("Failed to LoadPlayerData - No data found in the database.");
                    return;
                }

                PlayerList.Clear();
                OnlinePlayerList.Clear();
                int onlinePlayerCount = 0;

                foreach (DataRow row in playerDataTable.Rows)
                {
                    var playerData = new PlayerData();

                    foreach (var property in typeof(PlayerData).GetProperties())
                    {
                        if (playerDataTable.Columns.Contains(property.Name))
                        {
                            var value = row[property.Name];
                            if (value != DBNull.Value)
                            {
                                property.SetValue(playerData, Convert.ChangeType(value, property.PropertyType));
                            }
                        }
                    }

                    int stageId = playerData.StageIdx;

                    if (playerDataTable.Columns.Contains("LoginTime") && playerDataTable.Columns.Contains("LogoutTime"))
                    {
                        DateTime loginTime = Convert.ToDateTime(row["LoginTime"]);
                        DateTime logoutTime = Convert.ToDateTime(row["LogoutTime"]);

                        if (loginTime > logoutTime)
                        {
                            onlinePlayerCount++;
                            if (!OnlinePlayerList.ContainsKey(stageId))
                            {
                                OnlinePlayerList[stageId] = new List<PlayerData>();
                            }
                            OnlinePlayerList[stageId].Add(playerData);
                        }
                    }

                    if (!PlayerList.ContainsKey(stageId))
                    {
                        PlayerList[stageId] = new List<PlayerData>();
                    }

                    PlayerList[stageId].Add(playerData);
                }

                MapPage.AllPlayerCount = playerDataTable.Rows.Count;
                MapPage.AllOnlineCount = onlinePlayerCount;
                MapPage.LoadPlayerData();

                Log($"Loaded player data from database. [Total Players: {playerDataTable.Rows.Count}, Online Players: {onlinePlayerCount}]");
            }
            catch (Exception ex)
            {
                Log($"Error loading player data from database: {ex.Message}");
            }
        }

        private static void LoadStringTemplate()
        {
            dynamic jsonData = LoadJson("STRING_TEMPLATE.json");
            if (jsonData == null)
            {
                Log($"STRING_TEMPLATE.json not found.");
                return;
            }

            StringTemplateMap.Clear();
            int stringTemplateCount = 0;
            int successCount = 0;
            int failureCount = 0;
            int repeatCount = 0;

            try
            {
                foreach (var row in jsonData)
                {
                    stringTemplateCount++;
                    if (int.TryParse(row.Name, out int stringId))
                    {
                        string? text = row.Value?[Config.CurrentLanguage]?.ToString();
                        if (text != null)
                        {
                            if (StringTemplateMap.ContainsKey(stringId))
                            {
                                repeatCount++;
                            }
                            else
                            {
                                var templateData = new StringTemplateData { StringId = stringId, Text = text };
                                StringTemplateMap[stringId] = templateData;
                                successCount++;
                            }
                        }
                        else
                        {
                            failureCount++;
                        }
                    }
                    else
                    {
                        failureCount++;
                        Log($"Failed to parse StringId: {row.Name}");
                    }
                }

                Log($"Loaded {stringTemplateCount} string templates. [Success: {successCount}, Failures: {failureCount}, Repeats: {repeatCount}]");
            }
            catch (Exception ex)
            {
                Log($"Error loading STRING_TEMPLATE.json: {ex.Message}");
            }
        }

        private static void LoadStringMessage()
        {
            dynamic jsonData = LoadJson("STRING_MESSAGE.json");
            if (jsonData == null)
            {
                Log($"STRING_MESSAGE.json not found.");
                return;
            }

            StringMessageMap.Clear();
            int stringMessageCount = 0;
            int successCount = 0;
            int failureCount = 0;
            int repeatCount = 0;

            try
            {
                foreach (var row in jsonData)
                {
                    stringMessageCount++;
                    if (int.TryParse(row.Name, out int stringId))
                    {
                        string? text = row.Value?[Config.CurrentLanguage]?.ToString();
                        if (text != null)
                        {
                            if (StringMessageMap.ContainsKey(stringId))
                            {
                                repeatCount++;
                            }
                            else
                            {
                                var templateData = new StringMessageData { StringId = stringId, Text = text };
                                StringMessageMap[stringId] = templateData;
                                successCount++;
                            }
                        }
                        else
                        {
                            failureCount++;
                        }
                    }
                    else
                    {
                        failureCount++;
                        Log($"Failed to parse StringId: {row.Name}");
                    }
                }

                Log($"Loaded {stringMessageCount} string messages. [Success: {successCount}, Failures: {failureCount}, Repeats: {repeatCount}]");
            }
            catch (Exception ex)
            {
                Log($"Error loading STRING_MESSAGE.json: {ex.Message}");
            }
        }

        public static string GetStringTemplateById(int stringId)
        {
            if (StringTemplateMap.TryGetValue(stringId, out var strText))
            {
                return strText.Text ?? "Unknown";
            }
            return "Unknown";
        }

        public static string GetStringMessageById(int stringId)
        {
            if (StringMessageMap.TryGetValue(stringId, out var strText))
            {
                return strText.Text ?? "Unknown";
            }
            return "Unknown";
        }
        public static string CombineStringsWithSpaces(params (Func<int, string> getStringMethod, int id)[] stringSources)
        {
            string space = " ";
            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                space = "";

            string result = "";

            foreach (var (getStringMethod, id) in stringSources)
                result += getStringMethod(id) + space;

            if (result.EndsWith(space))
                result = result.Substring(0, result.Length - space.Length);

            return result;
        }

        public static string CombineStringsWithSpaces(Func<int, string> getStringByIdMethod, params int[] ids)
        {
            string space = " ";
            if (Config.NeedBoldTextLanguages.Contains(Config.CurrentLanguage))
                space = "";

            string result = "";

            foreach (var id in ids)
                result += getStringByIdMethod(id) + space;

            if (result.EndsWith(space))
                result = result.Substring(0, result.Length - space.Length);

            return result;
        }

        public static string GetFilePath(string relativePath)
        {
            string fullPath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, relativePath);
            return fullPath.Replace('/', '\\');
        }

        public static object LoadTabFile(string filePath, string format, string keyField, string[] fieldNames)
        {
            if (!File.Exists(filePath))
            {
                Log($"File not found at {filePath}");
                return keyField == null
                    ? (object)new List<Dictionary<string, object>>()
                    : (object)new Dictionary<object, List<Dictionary<string, object>>>();
            }

            var csvRecords = LoadCsv(filePath, fieldNames);

            if (csvRecords == null || !csvRecords.Any())
            {
                Log($"Failed to load file: {filePath}");
                return keyField == null
                    ? (object)new List<Dictionary<string, object>>()
                    : (object)new Dictionary<object, List<Dictionary<string, object>>>();
            }

            if (format.Length != fieldNames.Length)
            {
                throw new ArgumentException($"{filePath}: Format string length does not match the number of fields. format.Length: {format.Length} fieldNames.Length: {fieldNames.Length}");
            }

            var fieldFormats = fieldNames.Zip(format, (name, fmt) => new { name, fmt })
                                         .ToDictionary(x => x.name, x => x.fmt);

            if (keyField == null)
            {
                return csvRecords.Select(record =>
                    fieldNames
                        .Where(name => record.ContainsKey(name))
                        .ToDictionary(
                            name => name,
                            name => ParseField(record[name], fieldFormats[name])
                        )
                ).ToList();
            }
            else
            {
                bool isKeyFieldInt = format[fieldNames.ToList().IndexOf(keyField)] == 'd';

                if (isKeyFieldInt)
                {
                    var resultMap = new Dictionary<int, List<Dictionary<string, object>>>();
                    foreach (var record in csvRecords)
                    {
                        int key = int.Parse(record[keyField]);

                        if (!resultMap.ContainsKey(key))
                        {
                            resultMap[key] = new List<Dictionary<string, object>>();
                        }

                        var processedRecord = fieldNames
                            .Where(name => record.ContainsKey(name))
                            .ToDictionary(
                                name => name,
                                name => ParseField(record[name], fieldFormats[name])
                            );

                        resultMap[key].Add(processedRecord);
                    }

                    return resultMap;
                }
                else
                {
                    var resultMap = new Dictionary<string, List<Dictionary<string, object>>>();

                    foreach (var record in csvRecords)
                    {
                        string key = record[keyField];

                        if (!resultMap.ContainsKey(key))
                        {
                            resultMap[key] = new List<Dictionary<string, object>>();
                        }

                        var processedRecord = fieldNames
                            .Where(name => record.ContainsKey(name))
                            .ToDictionary(
                                name => name,
                                name => ParseField(record[name], fieldFormats[name])
                            );

                        resultMap[key].Add(processedRecord);
                    }

                    return resultMap;
                }
            }
        }

        private static object ParseField(string value, char format)
        {
            return format switch
            {
                'i' => double.TryParse(value, out double doubleValue) ? (object)doubleValue : 0,
                'd' => int.TryParse(value, out int intValue) ? (object)intValue : 0,
                'l' => long.TryParse(value, out long longValue) ? (object)longValue : 0,
                'f' => float.TryParse(value, out float floatValue) ? (object)floatValue : 0f,
                's' => value,
                'b' => bool.TryParse(value, out bool boolValue) ? (object)boolValue : false,
                'o' => ParseTableField(value),
                _ => value
            };
        }

        private static object ParseTableField(string fieldValue)
        {
            var result = new Dictionary<string, object>();
            var regex = new Regex(@"\s*(?<key>\w+)\s*=\s*(?<value>-?\d+(\.\d+)?)");
            var matches = regex.Matches(fieldValue);

            foreach (Match match in matches)
            {
                var key = match.Groups["key"].Value.ToUpper();
                var value = match.Groups["value"].Value;

                if (string.IsNullOrEmpty(value))
                {
                    Log($"Value for key '{key}' is empty or not correctly parsed.");
                    continue;
                }

                if (int.TryParse(value, out var intValue))
                    result[key] = intValue;
                else if (float.TryParse(value, out var floatValue))
                    result[key] = floatValue;
                else
                    result[key] = value;
            }

            if (result.Count > 0)
                return result;

            var listResult = new List<object>();
            var cleanedFieldValue = Regex.Matches(fieldValue, @"-?\d+(\.\d+)?");

            foreach (Match match in cleanedFieldValue)
            {
                var value = match.Value.Trim();

                if (int.TryParse(value, out var intValue))
                    listResult.Add(intValue);
                else if (float.TryParse(value, out var floatValue))
                    listResult.Add(floatValue);
                else
                    listResult.Add(value);
            }

            return listResult;
        }

        public static List<T> LoadJsonData<T>(string fileName) where T : new()
        {
            var data = LoadJson(fileName);

            if (data == null)
                return new List<T>();

            List<T> result = new List<T>();

            if (data is List<ExpandoObject> expandoList)
            {
                foreach (dynamic item in expandoList)
                {
                    var instance = new T();

                    foreach (var property in typeof(T).GetProperties(BindingFlags.Public | BindingFlags.Instance))
                    {
                        try
                        {
                            if (((IDictionary<string, object>)item).TryGetValue(property.Name, out var value))
                            {
                                if (value != null)
                                {
                                    if (value is JObject jObject)
                                    {
                                        var dict = jObject.ToObject(property.PropertyType);
                                        property.SetValue(instance, dict);
                                    }
                                    else if (value is JArray jArray)
                                    {
                                        var listType = property.PropertyType.GetGenericArguments().FirstOrDefault();
                                        var list = jArray.ToObject(typeof(List<>).MakeGenericType(listType));
                                        property.SetValue(instance, list);
                                    }
                                    else
                                    {
                                        var convertedValue = Convert.ChangeType(value, property.PropertyType);
                                        property.SetValue(instance, convertedValue);
                                    }
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            Log($"Error setting property {property.Name} for {typeof(T).Name}: {ex.Message}");
                        }
                    }
                    result.Add(instance);
                }
            }
            else if (data is JObject jObjectData)
            {
                foreach (var kvp in jObjectData)
                {
                    var instance = new T();
                    var properties = kvp.Value as JObject;

                    if (properties != null)
                    {
                        foreach (var property in typeof(T).GetProperties(BindingFlags.Public | BindingFlags.Instance))
                        {
                            try
                            {
                                if (properties.TryGetValue(property.Name, StringComparison.OrdinalIgnoreCase, out var value))
                                {
                                    if (value != null)
                                    {
                                        if (value is JObject innerJObject)
                                        {
                                            var dict = innerJObject.ToObject(property.PropertyType);
                                            property.SetValue(instance, dict);
                                        }
                                        else if (value is JArray innerJArray)
                                        {
                                            var listType = property.PropertyType.GetGenericArguments().FirstOrDefault();
                                            var list = innerJArray.ToObject(typeof(List<>).MakeGenericType(listType));
                                            property.SetValue(instance, list);
                                        }
                                        else
                                        {
                                            var convertedValue = Convert.ChangeType(value, property.PropertyType);
                                            property.SetValue(instance, convertedValue);
                                        }
                                    }
                                }
                            }
                            catch (Exception ex)
                            {
                                Log($"Error setting property {property.Name} for {typeof(T).Name}: {ex.Message}");
                            }
                        }
                    }
                    result.Add(instance);
                }
            }
            else
            {
                Log($"Unexpected JSON format in {fileName}");
                return new List<T>();
            }

            return result;
        }

        public static dynamic LoadJson(string fileName)
        {
            string jsonFilePath = GetFilePath($"Assets/Json/{fileName}");
            if (!File.Exists(jsonFilePath))
                return null;

            try
            {
                string jsonData = File.ReadAllText(jsonFilePath);
                var parsedJson = JsonConvert.DeserializeObject<JToken>(jsonData);

                if (parsedJson is JArray jsonArray)
                {
                    foreach (var item in jsonArray)
                    {
                        if (item["Rows"] is null)
                            continue;

                        return item["Rows"];
                    }

                    return jsonArray;
                }
                else
                {
                    return parsedJson;
                }
            }
            catch (Exception ex)
            {
                Log($"Error loading {fileName}: {ex.Message}");
                return null;
            }
        }

        public static List<T> LoadCsv<T>(string csvFilePath)
        {
            if (!File.Exists(csvFilePath))
            {
                MessageBox.Show($"CSV file not found at {csvFilePath}");
                return new List<T>();
            }

            try
            {
                using (var reader = new StreamReader(csvFilePath))
                using (var csv = new CsvReader(reader, new CsvConfiguration(CultureInfo.InvariantCulture)
                {
                    HeaderValidated = null,
                    MissingFieldFound = null
                }))
                {
                    return csv.GetRecords<T>().ToList();
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error loading CSV: {ex.Message}");
                return new List<T>();
            }
        }

        private static List<Dictionary<string, string>> LoadCsv(string csvFilePath, string[] fieldNames)
        {
            if (!File.Exists(csvFilePath))
            {
                MessageBox.Show($"CSV file not found at {csvFilePath}");
                return new List<Dictionary<string, string>>();
            }

            try
            {
                using (var reader = new StreamReader(csvFilePath))
                using (var csv = new CsvReader(reader, new CsvConfiguration(CultureInfo.InvariantCulture)
                {
                    HeaderValidated = null,
                    MissingFieldFound = null
                }))
                {
                    var records = new List<Dictionary<string, string>>();
                    csv.Read();
                    csv.ReadHeader();

                    var csvFieldHeaders = csv.HeaderRecord;

                    while (csv.Read())
                    {
                        var record = new Dictionary<string, string>();

                        foreach (var fieldName in fieldNames)
                        {
                            if (Array.IndexOf(csvFieldHeaders, fieldName) >= 0)
                                record[fieldName] = csv.GetField(fieldName);
                            else
                                record[fieldName] = null;
                        }
                        records.Add(record);
                    }

                    return records;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Error loading CSV: {ex.Message}");
                return new List<Dictionary<string, string>>();
            }
        }
    }

    public class ItemData
    {
        public int ItemID { get; set; }
        public int NameSid { get; set; }
        public int NoteSid { get; set; }
        public int Icon { get; set; }
        public int Level { get; set; }
        public int MainType { get; set; }
        public int SubType { get; set; }
        public int ClassId { get; set; }
        public int ReqClassLevel { get; set; }
        public int Tier { get; set; }
        public int Grade { get; set; }
        public int TradeType { get; set; }
        public override string ToString()
        {
            return $"{ItemID};{FileManager.GetStringTemplateById(NameSid)}" ?? string.Empty;
        }
    }

    public class MoneyData
    {
        public int OwnerType { get; set; }
        public int MoneyId { get; set; }
        public int NameSid { get; set; }
        public int NoteID { get; set; }
        public int Icon { get; set; }
        public int IconSmall { get; set; }
        public long LimitUserMax { get; set; }
        public int LimitGuildMax { get; set; }
        public int GetWayId { get; set; }
        public int ShopBuyId { get; set; }
    }

    public class IconData
    {
        public int IconId { get; set; }
        public string? Path { get; set; }
        public bool IsMultiLanguage { get; set; }
    }

    public class MapWorldData
    {
        public int Name { get; set; }
        public int AreaId { get; set; }
        public int AreaStringId { get; set; }
        public int ConquerServer { get; set; }
        public int AreaIconId { get; set; }
        public Dictionary<string, object>? AreaCoordinate { get; set; }
        public string? AreaMapResource { get; set; }
        public int MiniGroupId { get; set; }
        public int PrevAreaId { get; set; }
        public int NextAreaId { get; set; }
        public int TouchPosition_X { get; set; }
        public int TouchPosition_Y { get; set; }
        public int TouchScale_X { get; set; }
        public int TouchScale_Y { get; set; }
        public int MonLevelMin { get; set; }
        public int MonLevelMax { get; set; }
    }
    public class MapAreaData
    {
        public int Name { get; set; }
        public int RowId { get; set; }
        public int AreaId { get; set; }
        public int MiniGroupId { get; set; }
        public int MiniGroupStringId { get; set; }
        public int MiniGroupIcon { get; set; }
        public Dictionary<string, object>? MiniGroupCoordinate { get; set; }
        public int MapFunction { get; set; }
        public int MapContentID { get; set; }
        public int GoodsTabAble { get; set; }
        public int DominionIconId { get; set; }
        public int DominionId { get; set; }
        public int TouchPosition_X { get; set; }
        public int TouchPosition_Y { get; set; }
        public int TouchScale_X { get; set; }
        public int TouchScale_Y { get; set; }
        public int MonLevelMin { get; set; }
        public int MonLevelMax { get; set; }
        public int PartyPlay { get; set; }
    }

    public class MapMiniData
    {
        public int Name { get; set; }
        public int RowId { get; set; }
        public int MiniGroupId { get; set; }
        public int MiniStageId { get; set; }
        public int BidoMatrixStageId { get; set; }
        public Dictionary<string, object>? MiniMapCoordinateLT { get; set; }
        public Dictionary<string, object>? MiniMapCoordinateRB { get; set; }
        public int MiniMapZoom { get; set; }
        public float UserMovingRaderZoom { get; set; }
        public string? MiniMapResource { get; set; }
        public int ElliteCheck { get; set; }
        public int DefaultOpenList { get; set; }
        public int ElliteStageId { get; set; }
    }

    public class MapStageData
    {
        public int StageID { get; set; }
        public int StageNameSID { get; set; }
        public string? StageType { get; set; }
        public string? MapMiniInfo { get; set; }
        public string? SectorGrade { get; set; }
    }
    public class MapStageSectorData
    {
        public int SectorID { get; set; }
        public int StageID { get; set; }
        public int SectorGrade { get; set; }
        public int RewardCostRate { get; set; }
        public int LuckyRewardDropRate { get; set; }
        public int NameTagDropItemRate { get; set; }
        public int SummonGoblinRate { get; set; }
    }

    public class MapMiniInfoData
    {
        public string? InfoMainType { get; set; }
        public int InfoSubType { get; set; }
        public int InfoSubTypeValue { get; set; }
        public int InfoValue { get; set; }
        public int DetailInfoValue { get; set; }
        public int InfoStringId { get; set; }
        public string? InfoStringPosType { get; set; }
        public Dictionary<string, object>? InfoCoordinate { get; set; }
    }

    public class MapSectorGradeData
    {
        public int SectorID { get; set; }
        public float SectorRange { get; set; }
        public Dictionary<string, object>? SectorLocation { get; set; }
    }

    public class ItemViewerData
    {
        public int StageId { get; set; }
        public List<int>? Money { get; set; }
        public List<int>? TimeTicket { get; set; }
        public List<int>? ClassItem01 { get; set; }
        public List<int>? ClassItem02 { get; set; }
        public List<int>? ClassItem03 { get; set; }
        public List<int>? ClassItem04 { get; set; }
        public List<int>? ClassItem05 { get; set; }
        public List<int>? ClassItem06 { get; set; }
        public List<int>? ClassItemCountMin01 { get; set; }
        public List<int>? ClassItemCountMin02 { get; set; }
        public List<int>? ClassItemCountMin03 { get; set; }
        public List<int>? ClassItemCountMin04 { get; set; }
        public List<int>? ClassItemCountMin05 { get; set; }
        public List<int>? ClassItemCountMin06 { get; set; }
        public List<int>? ClassItemCountMax01 { get; set; }
        public List<int>? ClassItemCountMax02 { get; set; }
        public List<int>? ClassItemCountMax03 { get; set; }
        public List<int>? ClassItemCountMax04 { get; set; }
        public List<int>? ClassItemCountMax05 { get; set; }
        public List<int>? ClassItemCountMax06 { get; set; }
    }

    public class SpecialMapData
    {
        public int MapId { get; set; }
        public string? MapName { get; set; }
        public override string ToString()
        {
            return MapName ?? string.Empty;
        }
    }

    public class PlayerData
    {
        public int CharacterUID { get; set; }
        public string? CharacterName { get; set; }
        public int Class { get; set; }
        public int Lev { get; set; }
        public int StageIdx { get; set; }
        public double PositionX { get; set; }
        public double PositionY { get; set; }
        public override string ToString()
        {
            return $"{CharacterUID};{CharacterName}" ?? string.Empty;
        }
    }

    public class StringTemplateData
    {
        public int StringId { get; set; }
        public string? Text { get; set; }
    }

    public class StringMessageData
    {
        public int StringId { get; set; }
        public int ChattingMessageType { get; set; }
        public int MessageType { get; set; }
        public string? Text { get; set; }
        public int TextColorID { get; set; }
    }
}
