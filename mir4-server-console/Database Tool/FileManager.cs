using System;
using System.Collections.Concurrent;
using System.Collections.Generic;
using System.Globalization;
using System.IO;
using System.Linq;
using CsvHelper.Configuration;
using CsvHelper;
using Microsoft.VisualBasic.FileIO;
using Newtonsoft.Json.Linq;
using System.Text.RegularExpressions;
using System.Reflection;
using System.Collections;

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
        public static Dictionary<int, List<MapMiniData>> MapMiniList { get; private set; } = new Dictionary<int, List<MapMiniData>>();
        public static Dictionary<int, MapStageData> MapStageList { get; private set; } = new Dictionary<int, MapStageData>();
        public static Dictionary<int, ItemViewerData> ItemViewerList { get; private set; } = new Dictionary<int, ItemViewerData>();
        public static Dictionary<int, List<PlayerData>> PlayerList { get; private set; } = new Dictionary<int, List<PlayerData>>();
        public static ConcurrentDictionary<string, ItemData> CombinedIndex { get; private set; } = new ConcurrentDictionary<string, ItemData>();

        private static void Log(string message) => DatabaseTool.Log(message);

        public static void Initialize()
        {
            LoadItemData();
            LoadMoneyData();
            LoadIconData();
            LoadStringTemplate();
            LoadStringMessage();
            LoadWorldMapData();
            LoadAreaMapData();
            LoadMiniMapData();
            LoadStageMapData();
            LoadItemViewerData();
            LoadPlayerData();
            BuildCombinedIndex();
            ItemPage.LoadComboBox();
        }

        public static void LoadItemData()
        {
            var itemDataMap = LoadTabFile(
                "Assets/CSV/ITEM.csv",
                "dddddldddddddddddddddddddddddddddddddddddddddddddddd",
                "ItemId",
                new[]
                {
                    "Name", "ItemId", "UseId", "NameSid", "NoteSid", "MeshId", "Icon", "Level", "MainType", "SubType",
                    "SmeltingType", "SortOrder", "ClassId", "ReqClassLevel", "Tier", "Grade", "PET_Origin",
                    "SellType", "SellPrice", "Stackable", "CoolTime", "BuffId", "ItemOptionType", "SetGroupId",
                    "ReinforceMaxLv", "SmeltingMaxCount", "JewelUpgradeType", "JewelUpgradeCount",
                    "RandomGetInfoId", "SummonGroupId", "CastingType", "CastingTime", "SummonStageType",
                    "ItemCasting", "CollectAniType", "GetWayId", "OverlapEquipAble", "UnsealingType",
                    "OpenboxRewardType", "OpenboxRewardId", "RandomGetCnt", "Durability", "Durability_RepairAble",
                    "TradeType", "UseTimeType", "UseEndType_Period", "UseEndType_Minute", "Lockable",
                    "XDracoDelayMinute", "GachaTap", "TranceGroup", "EquipGroup"
                }
            ) as Dictionary<int, List<Dictionary<string, object>>>;

            if (itemDataMap == null || !itemDataMap.Any())
            {
                Log("Failed to LoadItemData");
                return;
            }

            ItemMap.Clear();

            var formattedItemMap = new Dictionary<int, ItemData>();

            foreach (var kvp in itemDataMap)
            {
                var itemData = new ItemData();
                var itemDict = kvp.Value.FirstOrDefault();

                if (itemDict != null)
                {
                    foreach (var property in typeof(ItemData).GetProperties(BindingFlags.Public | BindingFlags.Instance))
                    {
                        if (itemDict.ContainsKey(property.Name))
                        {
                            var value = itemDict[property.Name];
                            if (value != null)
                            {
                                property.SetValue(itemData, Convert.ChangeType(value, property.PropertyType));
                            }
                        }
                    }
                }

                formattedItemMap[kvp.Key] = itemData;
            }

            ItemMap = formattedItemMap;

            Log($"Loaded {ItemMap.Count} items.");
        }

        public static void LoadMoneyData()
        {
            var moneyDataMap = LoadTabFile(
                "Assets/CSV/MONEY.csv",
                "dddddddddd",
                null,
                new[]
                {
                    "OwnerType", "MoneyId", "NameSid", "NoteID", "Icon", "IconSmall", "LimitUserMax", "LimitGuildMax", "GetWayId", "ShopBuyId"
                }
            ) as List<Dictionary<string, object>>;

            if (moneyDataMap == null || !moneyDataMap.Any())
            {
                Log("Failed to LoadMoneyData");
                return;
            }

            MoneyMap.Clear();

            foreach (var fields in moneyDataMap)
            {
                var moneyData = new MoneyData();

                foreach (var property in typeof(MoneyData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(moneyData, value);
                        else
                            property.SetValue(moneyData, Convert.ChangeType(value, property.PropertyType));
                    }
                }
                var areaId = (int)fields["MoneyId"];
                MoneyMap[areaId] = moneyData;
            }

            Log($"Loaded {MoneyMap.Count} moneys.");
        }

        private static void LoadIconData()
        {
            var iconDataMap = LoadTabFile("Assets/CSV/ICON.csv", "dddsd", "IconId", new[] { "Name", "IconId", "Type", "Path", "IsMultiLanguage" }) as Dictionary<int, List<Dictionary<string, object>>>;

            if (iconDataMap == null || !iconDataMap.Any())
            {
                Log("Failed to LoadIconData");
                return;
            }

            IconPathMap.Clear();
            foreach (var kvp in iconDataMap)
            {
                var path = kvp.Value.First()["Path"] as string ?? string.Empty;
                IconPathMap[kvp.Key] = path;
            }

            Log($"Loaded {IconPathMap.Count} icons.");
        }

        private static void LoadWorldMapData()
        {
            var mapWorldDataList = LoadTabFile(
                "Assets/CSV/MAPUI_WORLD.csv",
                "dddosddddddddd",
                null,
                new[]
                {
                    "AreaId", "AreaStringId", "AreaIconId", "AreaCoordinate",
                    "AreaMapResource", "MiniGroupId", "PrevAreaId", "NextAreaId",
                    "TouchPosition_X", "TouchPosition_Y", "TouchScale_X", "TouchScale_Y",
                    "MonLevelMin", "MonLevelMax"
                }
            ) as List<Dictionary<string, object>>;

            if (mapWorldDataList == null || !mapWorldDataList.Any())
            {
                Log("Failed to LoadWorldMapData");
                return;
            }

            MapWorldList.Clear();

            foreach (var fields in mapWorldDataList)
            {
                var mapData = new MapWorldData();

                foreach (var property in typeof(MapWorldData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(mapData, value);
                        else
                            property.SetValue(mapData, Convert.ChangeType(value, property.PropertyType));
                    }
                }
                var areaId = (int)fields["AreaId"];
                MapWorldList[areaId] = mapData;
            }

            Log($"Loaded {MapWorldList.Count} world maps.");
        }

        private static void LoadAreaMapData()
        {
            var mapAreaDataList = LoadTabFile(
                "Assets/CSV/MAPUI_AREA.csv",
                "ddddodddddddddddd",
                null,
                new[]
                {
                    "AreaId", "MiniGroupId", "MiniGroupStringId", "MiniGroupIcon",
                    "MiniGroupCoordinate", "MapFunction", "MapContentID", "GoodsTabAble",
                    "DominionIconId", "DominionId", "TouchPosition_X", "TouchPosition_Y",
                    "TouchScale_X", "TouchScale_Y", "MonLevelMin", "MonLevelMax", "PartyPlay"
                }
            ) as List<Dictionary<string, object>>;

            if (mapAreaDataList == null || !mapAreaDataList.Any())
            {
                Log("Failed to LoadAreaMapData");
                return;
            }

            MapAreaList.Clear();

            foreach (var fields in mapAreaDataList)
            {
                var mapData = new MapAreaData();

                foreach (var property in typeof(MapAreaData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(mapData, value);
                        else
                            property.SetValue(mapData, Convert.ChangeType(value, property.PropertyType));
                    }
                }

                var areaId = (int)fields["AreaId"];
                if (!MapAreaList.ContainsKey(areaId))
                    MapAreaList[areaId] = new List<MapAreaData>();

                MapAreaList[areaId].Add(mapData);
            }

            Log($"Loaded {MapAreaList.Count} area maps.");
        }

        private static void LoadMiniMapData()
        {
            var mapMiniDataList = LoadTabFile(
                "Assets/CSV/MAPUI_MINI.csv",
                "dddoodfsddd",
                null,
                new[]
                {
                    "MiniGroupId", "MiniStageId", "BidoMatrixStageId", "MiniMapCoordinateLT", "MiniMapCoordinateRB",
                    "MiniMapZoom", "UserMovingRaderZoom", "MiniMapResource", "ElliteCheck", "DefaultOpenList", "ElliteStageId"
                }
            ) as List<Dictionary<string, object>>;

            if (mapMiniDataList == null || !mapMiniDataList.Any())
            {
                Log("Failed to LoadMiniMapData");
                return;
            }

            MapMiniList.Clear();

            foreach (var fields in mapMiniDataList)
            {
                var mapData = new MapMiniData();

                foreach (var property in typeof(MapMiniData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(mapData, value);
                        else
                            property.SetValue(mapData, Convert.ChangeType(value, property.PropertyType));
                    }
                }

                var miniGroupId = (int)fields["MiniGroupId"];
                if (!MapMiniList.ContainsKey(miniGroupId))
                {
                    MapMiniList[miniGroupId] = new List<MapMiniData>();
                }

                MapMiniList[miniGroupId].Add(mapData);
            }

            Log($"Loaded {MapMiniList.Count} mini maps.");
        }

        private static void LoadStageMapData()
        {
            var mapStageDataList = LoadTabFile(
                "Assets/CSV/STAGE.csv",
                "dddddddddddddddddddddddddddddddddddddddddddddddddssssssddddddsssdoooodddddddddddddddddddddd",
                null,
                new[]
                {
                    "StageID", "StageType", "ChannelId", "StageNameSID", "QuestStageNameSID", "LoadingId", "ThemeID", "CameraPreset",
                    "BlockSize", "ChaVRadius", "RevivalId", "KickRevivalId", "RestartWarpLocation", "KillZone", "StageLevel", "CombatPower",
                    "EnableLevel", "EnableQuest", "EnableCombatPower", "EnableGreatBuildingID", "EnableBuildingStep", "EnablePkGrade", "AirWalkban",
                    "DirectWarpAble", "BoardOnOff", "CanMeditation", "GuildSanctumId", "SummonItemUseType", "EXPPenalty", "ReturnWarpLocation",
                    "ReturnWarpItemUseId", "DirectWarpNeedCostId", "DirectWarpNeedCostCount", "DirectWarpNeedItemId", "DirectWarpNeedItemCount",
                    "DirectWarpNeedItemUseId", "WaitingWarpLocation", "PkOnOff", "PkOnOffNonPK", "PKGuardOff", "PKtendency", "Durability", "StageRule",
                    "DeathPenalty_Durability", "DeathPenalty_Injure", "DeathPenalty_Steal", "DeathPenalty_StealValue", "DeathPenalty_EXP", "IconId",
                    "MapMiniInfo", "GraphicLevel", "TriggerLevel", "Gendata", "GenDoorData", "DungeonScriptData", "SpawnDataID", "NaviMeshId_Main",
                    "NaviMeshId_Sub01", "IsPossibleMacroMode", "StandardPosition_X", "StandardPosition_Y", "WayPoint", "SafetyArea", "SectorGrade",
                    "GetWayType", "GatheringZone", "MiningZone", "SpiritZone", "GetWayCoordinate", "SequenceType", "SequencePlayId", "Repeat",
                    "DominionId_Inter", "DominionId_Siege", "DominionId_StrongPoint", "IsInterServer", "DominionId_Grade1", "DominionId_Grade2",
                    "DominionId_Grade3", "GuildWarPlunderId", "SeverVisit", "SeverVisitRevivalId", "SeverVisitNoVitality", "ConquerSeverNoVitality",
                    "Mapmark", "Union_Zone", "GuildAltarId", "PkPenalty", "PkDeathPanalty", "PkSteal", "PkDurability"
                }
            ) as List<Dictionary<string, object>>;

            if (mapStageDataList == null || !mapStageDataList.Any())
            {
                Log("Failed to LoadStageMapData");
                return;
            }

            MapStageList.Clear();

            foreach (var fields in mapStageDataList)
            {
                var mapData = new MapStageData();

                foreach (var property in typeof(MapStageData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(mapData, value);
                        else
                            property.SetValue(mapData, Convert.ChangeType(value, property.PropertyType));
                    }
                }

                var stageId = (int)fields["StageID"];
                MapStageList[stageId] = mapData;
            }

            Log($"Loaded {MapStageList.Count} stage maps.");
        }

        private static void LoadItemViewerData()
        {
            var itemViewerDataList = LoadTabFile(
                "Assets/CSV/ITEM_VIEWER.csv",
                "doooooooooooooooooooo",
                null,
                new[]
                {
                    "StageId", "Money", "TimeTicket",
                    "ClassItem01", "ClassItem02", "ClassItem03", "ClassItem04", "ClassItem05", "ClassItem06",
                    "ClassItemCountMin01", "ClassItemCountMin02", "ClassItemCountMin03", "ClassItemCountMin04",
                    "ClassItemCountMin05", "ClassItemCountMin06",
                    "ClassItemCountMax01", "ClassItemCountMax02", "ClassItemCountMax03", "ClassItemCountMax04",
                    "ClassItemCountMax05", "ClassItemCountMax06"
                }
            ) as List<Dictionary<string, object>>;

            if (itemViewerDataList == null || !itemViewerDataList.Any())
            {
                Log("Failed to LoadItemViewerData");
                return;
            }

            ItemViewerList.Clear();

            foreach (var fields in itemViewerDataList)
            {
                var itemViewerData = new ItemViewerData();

                foreach (var property in typeof(ItemViewerData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (property.PropertyType == typeof(List<int>) && value is List<object> listObject)
                        {
                            var intList = listObject.OfType<int>().ToList();
                            property.SetValue(itemViewerData, intList);
                        }
                        else
                        {
                            property.SetValue(itemViewerData, value);
                        }
                    }
                }

                var stageId = (int)fields["StageId"];
                ItemViewerList[stageId] = itemViewerData;
            }

            Log($"Loaded {ItemViewerList.Count} item viewer data.");
        }

        private static void LoadPlayerData()
        {
            var playerDataList = LoadTabFile(
                "Assets/CSV/character_tb.csv",
                "sdddii",
                null,
                new[]
                {
                    "CharacterName", "Class", "Lev", "StageIdx", "PositionX", "PositionY"
                }
            ) as List<Dictionary<string, object>>;

            if (playerDataList == null || !playerDataList.Any())
            {
                Log("Failed to LoadMiniMapData");
                return;
            }

            PlayerList.Clear();

            int playerCount = 0;
            foreach (var fields in playerDataList)
            {
                var playerData = new PlayerData();

                foreach (var property in typeof(PlayerData).GetProperties())
                {
                    if (fields.TryGetValue(property.Name, out var value))
                    {
                        if (value is Dictionary<string, object> dictionary)
                            property.SetValue(playerData, value);
                        else
                            property.SetValue(playerData, Convert.ChangeType(value, property.PropertyType));
                    }
                }

                var stageId = (int)fields["StageIdx"];
                if (!PlayerList.ContainsKey(stageId))
                {
                    PlayerList[stageId] = new List<PlayerData>();
                }

                playerCount++;
                PlayerList[stageId].Add(playerData);
            }

            MapPage.AllPlayerCount = playerCount;

            Log($"Loaded {playerCount} player data.");
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
            int repeatCount = 0;

            try
            {
                string jsonData = File.ReadAllText(jsonFilePath);
                var stringTemplates = JArray.Parse(jsonData);

                foreach (var template in stringTemplates)
                {
                    var rowData = template["Rows"] as JObject;
                    if (rowData != null)
                    {
                        foreach (var rowEntry in rowData.Properties())
                        {
                            stringTemplateCount++;
                            if (int.TryParse(rowEntry.Name, out int stringId))
                            {
                                string? chsValue = rowEntry.Value?[Config.CurrentLanguage]?.ToString();
                                if (chsValue != null)
                                {
                                    if (StringTemplateMap.ContainsKey(stringId))
                                    {
                                        repeatCount++;
                                    }
                                    else
                                    {
                                        var templateData = new StringTemplateData { StringId = stringId, Text = chsValue };
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
                                Log($"Failed to parse StringId: {rowEntry.Name}");
                            }
                        }
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
            string jsonFilePath = GetFilePath("Assets/Json/STRING_MESSAGE.json");
            if (!File.Exists(jsonFilePath))
            {
                Log($"STRING_MESSAGE.json not found at {jsonFilePath}");
                return;
            }

            StringMessageMap.Clear();
            int stringMessageCount = 0;
            int successCount = 0;
            int failureCount = 0;
            int repeatCount = 0;

            try
            {
                string jsonData = File.ReadAllText(jsonFilePath);
                var stringMessages = JArray.Parse(jsonData);

                foreach (var template in stringMessages)
                {
                    var rowData = template["Rows"] as JObject;
                    if (rowData != null)
                    {
                        foreach (var rowEntry in rowData.Properties())
                        {
                            stringMessageCount++;
                            if (int.TryParse(rowEntry.Name, out int stringId))
                            {
                                string? chsValue = rowEntry.Value?[Config.CurrentLanguage]?.ToString();
                                if (chsValue != null)
                                {
                                    if (StringMessageMap.ContainsKey(stringId))
                                    {
                                        repeatCount++;
                                    }
                                    else
                                    {
                                        var templateData = new StringMessageData { StringId = stringId, Text = chsValue };
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
                                Log($"Failed to parse StringId: {rowEntry.Name}");
                            }
                        }
                    }
                }

                Log($"Loaded {stringMessageCount} string messages. [Success: {successCount}, Failures: {failureCount}, Repeats: {repeatCount}]");
            }
            catch (Exception ex)
            {
                Log($"Error loading STRING_MESSAGE.json: {ex.Message}");
            }
        }

        public static void BuildCombinedIndex()
        {
            CombinedIndex.Clear();
            foreach (var item in ItemMap.Values)
            {
                if (StringTemplateMap.TryGetValue(item.NameSid, out var template))
                {
                    string key = $"{item.ItemId};{template.Text}";
                    CombinedIndex[key] = item;
                }
            }
        }
        public static string GetStringTemplateById(int stringId)
        {
            if (FileManager.StringTemplateMap.TryGetValue(stringId, out var strText))
            {
                return strText.Text;
            }
            return "Unknown";
        }

        public static string GetStringMessageById(int stringId)
        {
            if (FileManager.StringMessageMap.TryGetValue(stringId, out var strText))
            {
                return strText.Text;
            }
            return "Unknown";
        }

        public static List<string> GetMatchingItems(string searchTerm)
        {
            return string.IsNullOrEmpty(searchTerm) ? CombinedIndex.Keys.ToList() : CombinedIndex.Keys.Where(entry => entry.Contains(searchTerm, StringComparison.OrdinalIgnoreCase)).ToList();
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

        private static JArray LoadJson(string fileName)
        {
            string jsonFilePath = GetFilePath($"Assets/Json/{fileName}");
            if (!File.Exists(jsonFilePath))
            {
                Log($"{fileName} not found at {jsonFilePath}");
                return null;
            }

            try
            {
                string jsonData = File.ReadAllText(jsonFilePath);
                return JArray.Parse(jsonData);
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
        public int Name { get; set; }
        public int ItemId { get; set; }
        public int UseId { get; set; }
        public int NameSid { get; set; }
        public int NoteSid { get; set; }
        public long MeshId { get; set; }
        public int Icon { get; set; }
        public int Level { get; set; }
        public int MainType { get; set; }
        public int SubType { get; set; }
        public int SmeltingType { get; set; }
        public int SortOrder { get; set; }
        public int ClassId { get; set; }
        public int ReqClassLevel { get; set; }
        public int Tier { get; set; }
        public int Grade { get; set; }
        public int PET_Origin { get; set; }
        public int SellType { get; set; }
        public int SellPrice { get; set; }
        public int Stackable { get; set; }
        public int CoolTime { get; set; }
        public int BuffId { get; set; }
        public int ItemOptionType { get; set; }
        public int SetGroupId { get; set; }
        public int ReinforceMaxLv { get; set; }
        public int SmeltingMaxCount { get; set; }
        public int JewelUpgradeType { get; set; }
        public int JewelUpgradeCount { get; set; }
        public int RandomGetInfoId { get; set; }
        public int SummonGroupId { get; set; }
        public int CastingType { get; set; }
        public int CastingTime { get; set; }
        public int SummonStageType { get; set; }
        public int ItemCasting { get; set; }
        public int CollectAniType { get; set; }
        public int GetWayId { get; set; }
        public int OverlapEquipAble { get; set; }
        public int UnsealingType { get; set; }
        public int OpenboxRewardType { get; set; }
        public int OpenboxRewardId { get; set; }
        public int RandomGetCnt { get; set; }
        public int Durability { get; set; }
        public int Durability_RepairAble { get; set; }
        public int TradeType { get; set; }
        public int UseTimeType { get; set; }
        public int UseEndType_Period { get; set; }
        public int UseEndType_Minute { get; set; }
        public int Lockable { get; set; }
        public int XDracoDelayMinute { get; set; }
        public int GachaTap { get; set; }
        public int TranceGroup { get; set; }
        public int EquipGroup { get; set; }
    }

    public class MoneyData
    {
        public int OwnerType { get; set; }
        public int MoneyId { get; set; }
        public int NameSid { get; set; }
        public int NoteID { get; set; }
        public int Icon { get; set; }
        public int IconSmall { get; set; }
        public int LimitUserMax { get; set; }
        public int LimitGuildMax { get; set; }
        public int GetWayId { get; set; }
        public int ShopBuyId { get; set; }
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
        public Dictionary<string, object> MiniGroupCoordinate { get; set; }
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
        public int StageType { get; set; }
        public int ChannelId { get; set; }
        public int StageNameSID { get; set; }
        public int QuestStageNameSID { get; set; }
        public int LoadingId { get; set; }
        public int ThemeID { get; set; }
        public int CameraPreset { get; set; }
        public int BlockSize { get; set; }
        public int ChaVRadius { get; set; }
        public int RevivalId { get; set; }
        public int KickRevivalId { get; set; }
        public int RestartWarpLocation { get; set; }
        public int KillZone { get; set; }
        public int StageLevel { get; set; }
        public int CombatPower { get; set; }
        public int EnableLevel { get; set; }
        public int EnableQuest { get; set; }
        public int EnableCombatPower { get; set; }
        public int EnableGreatBuildingID { get; set; }
        public int EnableBuildingStep { get; set; }
        public int EnablePkGrade { get; set; }
        public int AirWalkban { get; set; }
        public int DirectWarpAble { get; set; }
        public int BoardOnOff { get; set; }
        public int CanMeditation { get; set; }
        public int GuildSanctumId { get; set; }
        public int SummonItemUseType { get; set; }
        public int EXPPenalty { get; set; }
        public int ReturnWarpLocation { get; set; }
        public int ReturnWarpItemUseId { get; set; }
        public int DirectWarpNeedCostId { get; set; }
        public int DirectWarpNeedCostCount { get; set; }
        public int DirectWarpNeedItemId { get; set; }
        public int DirectWarpNeedItemCount { get; set; }
        public int DirectWarpNeedItemUseId { get; set; }
        public int WaitingWarpLocation { get; set; }
        public int PkOnOff { get; set; }
        public int PkOnOffNonPK { get; set; }
        public int PKGuardOff { get; set; }
        public int PKtendency { get; set; }
        public int Durability { get; set; }
        public int StageRule { get; set; }
        public int DeathPenalty_Durability { get; set; }
        public int DeathPenalty_Injure { get; set; }
        public int DeathPenalty_Steal { get; set; }
        public int DeathPenalty_StealValue { get; set; }
        public int DeathPenalty_EXP { get; set; }
        public int IconId { get; set; }
        public string? MapMiniInfo { get; set; }
        public string? GraphicLevel { get; set; }
        public string? TriggerLevel { get; set; }
        public string? Gendata { get; set; }
        public string? GenDoorData { get; set; }
        public string? DungeonScriptData { get; set; }
        public int SpawnDataID { get; set; }
        public int NaviMeshId_Main { get; set; }
        public int NaviMeshId_Sub01 { get; set; }
        public int IsPossibleMacroMode { get; set; }
        public int StandardPosition_X { get; set; }
        public int StandardPosition_Y { get; set; }
        public string? WayPoint { get; set; }
        public string? SafetyArea { get; set; }
        public string? SectorGrade { get; set; }
        public int GetWayType { get; set; }
        public Dictionary<string, object>? GatheringZone { get; set; }
        public Dictionary<string, object>? MiningZone { get; set; }
        public Dictionary<string, object>? SpiritZone { get; set; }
        public Dictionary<string, object>? GetWayCoordinate { get; set; }
        public int SequenceType { get; set; }
        public int SequencePlayId { get; set; }
        public int Repeat { get; set; }
        public int DominionId_Inter { get; set; }
        public int DominionId_Siege { get; set; }
        public int DominionId_StrongPoint { get; set; }
        public int IsInterServer { get; set; }
        public int DominionId_Grade1 { get; set; }
        public int DominionId_Grade2 { get; set; }
        public int DominionId_Grade3 { get; set; }
        public int GuildWarPlunderId { get; set; }
        public int SeverVisit { get; set; }
        public int SeverVisitRevivalId { get; set; }
        public int SeverVisitNoVitality { get; set; }
        public int ConquerSeverNoVitality { get; set; }
        public int Mapmark { get; set; }
        public int Union_Zone { get; set; }
        public int GuildAltarId { get; set; }
        public int PkPenalty { get; set; }
        public int PkDeathPanalty { get; set; }
        public int PkSteal { get; set; }
        public int PkDurability { get; set; }
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

    public class PlayerData
    {
        public string? CharacterName { get; set; }
        public int Class { get; set; }
        public int Lev { get; set; }
        public int StageIdx { get; set; }
        public double PositionX { get; set; }
        public double PositionY { get; set; }
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
