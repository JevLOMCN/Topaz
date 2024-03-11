using System.Text;

namespace UnrealEngine.Gvas.Tokens;

public class SaveFileTokenizer
{
    public static void PrintSaveFile(string path)
    {
        var bytes = File.ReadAllBytes(path);

        var tokenList = new List<Token>();
        string currentString = string.Empty;
        int readChars = 0;
        for (int i = 0; i < bytes.Length; i++)
        {
            var b = bytes[i];
            if (b >= ' ' && b <= '~')
            {
                currentString += (char) b;
                readChars++;
            }
            else
            {
                if (readChars > 0)
                {
                    if (i - readChars - 4 > 0)
                    {
                        var prefixBytes = new[] {bytes[i - readChars - 4], bytes[i - readChars - 3], bytes[i - readChars - 2], bytes[i - readChars - 1]};
                        int stringLengthPrefix = BitConverter.ToInt32(prefixBytes, 0);
                        if (stringLengthPrefix is > 2 and < 128 && stringLengthPrefix == readChars + 1) // Sanity check with 128 max length
                            tokenList.Add(new Token(currentString, i));
                    }
                    else
                        tokenList.Add(new Token(currentString, i));
                }

                currentString = string.Empty;
                readChars = 0;
            }
        }

        int firstStringOffset = tokenList[1].Offset + 9 + 4 + BitConverter.ToInt32(bytes.Skip(tokenList[1].Offset + 5).Take(4).ToArray(), 0) * 20;
        if (tokenList[0] == "GVAS")
            tokenList = tokenList.Where(t => t.Offset > firstStringOffset).Skip(1).ToList();
        var tokenReader = new TokenReader(bytes, tokenList);

        Console.Out.WriteLine("[SaveData]");
        while (ReadAndPrintProperty(tokenReader, 1))
            continue;
    }

    private static bool ReadAndPrintProperty(TokenReader reader, int indentLevel = 0)
    {
        var name = reader.ReadNext();
        if (name == null!)
            return false;
        if (name == "None")
        {
            Console.Out.WriteLine(Indent(indentLevel - 1) + name);
            return false;
        }

        var type = reader.ReadNext();

        ReadAndPrintPropertyValue(reader, type, name, null, indentLevel);

        return true;
    }

    private static void ReadAndPrintPropertyValue(TokenReader reader, string? type, string? name, string? knownDataType = null, int indentLevel = 0)
    {
        if (type == "StructProperty")
            ReadAndPrintStructProperty(reader, name!, knownDataType, indentLevel);
        else if (type == "SetProperty")
            ReadAndPrintSetProperty(reader, name!, indentLevel);
        else if (type == "ArrayProperty")
            ReadAndPrintArrayProperty(reader, name!, indentLevel);
        else if (type == "EnumProperty")
            ReadAndPrintEnumProperty(reader, name!, indentLevel);
        else if (type == "MapProperty")
            ReadAndPrintMapProperty(reader, name!, indentLevel);
        else if (type == "ByteProperty")
            ReadAndPrintByteProperty(reader, name!, indentLevel);
        else if (type == "BoolProperty")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type}");
        else if (type == "NameProperty")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type} -> \"{reader.ReadNext()}\"");
        else if (type == "Int64Property")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type}");
        else if (type == "IntProperty")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type}");
        else if (type == "FloatProperty")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type}");
        else if (type is "StrProperty" or "ObjectProperty")
            Console.Out.WriteLine(Indent(indentLevel) + $"{name}: {type} -> \"{reader.ReadNext()}\"");
        else
            throw new Exception($"Property type not supported: {type}");
    }

    private static void ReadAndPrintByteProperty(TokenReader reader, string byteName, int indentLevel = 0)
    {
        var fieldLength = (int) BitConverter.ToInt64(reader.RawData.Skip(reader.Current.Offset - reader.Current.Text.Length - 4 - 8).Take(8).ToArray(), 0);
        var enumName = reader.ReadNext();
        var payloadData = reader.RawData.Skip(reader.Previous.Offset + 2).Take(fieldLength).ToArray();
        string payloadString = "(binary data)";
        if (payloadData.Length > 4 && payloadData.Length == BitConverter.ToInt32(payloadData, 0) + 4)
            payloadString = "\"" + Encoding.ASCII.GetString(payloadData.Skip(4).ToArray()).TrimEnd('\0') + "\"";
        Console.Out.WriteLine(Indent(indentLevel) + $"{byteName}: ByteProperty[{enumName}, {payloadString}, {fieldLength}d]");

        var dataEnd = reader.Previous.Offset + 2 + fieldLength;
        reader.SkipAfterOffset = dataEnd;
    }

    private static void ReadAndPrintMapProperty(TokenReader reader, string mapName, int indentLevel = 0)
    {
        var keyType = reader.ReadNext();
        var valueType = reader.ReadNext();
        int elementCount = BitConverter.ToInt32(reader.RawData.Skip(valueType!.Offset + 6).Take(4).ToArray(), 0);

        Console.Out.WriteLine(Indent(indentLevel) + $"{mapName}: MapProperty[{keyType}, {valueType}, {elementCount}d]");

        for (int i = 0; i < elementCount; i++)
        {
            ReadAndPrintPropertyValue(reader, keyType, "[key]", null, indentLevel + 1);
            ReadAndPrintPropertyValue(reader, valueType, "[value]", "[anonymous]", indentLevel + 1);
        }

        Console.Out.WriteLine(Indent(indentLevel) + $"[EndOf: {mapName}]");
    }

    private static void ReadAndPrintEnumProperty(TokenReader reader, string enumName, int indentLevel = 0)
    {
        var enumValue = reader.ReadNext()!.Text;
        string enumType;
        if (enumValue.Contains("::"))
        {
            enumType = enumValue.Split("::")[0];
            enumValue = enumValue.Split("::")[1];
        }
        else
        {
            enumType = enumValue;
            enumValue = reader.ReadNext()!.Text.Split("::")[1];
        }

        Console.Out.WriteLine(Indent(indentLevel) + $"{enumName}: EnumProperty {enumType} {enumType}::{enumValue}");
    }

    private static void ReadAndPrintArrayProperty(TokenReader reader, string arrayName, int indentLevel = 0)
    {
        var dataType = reader.ReadNext();
        int arraySize = BitConverter.ToInt32(reader.RawData.Skip(dataType!.Offset + 2).Take(4).ToArray(), 0);
        long totalFieldSize = BitConverter.ToInt64(reader.RawData.Skip(dataType.Offset - dataType.Text.Length - 4 - 8).Take(8).ToArray(), 0) - 4;
        long dataEnd = dataType.Offset + 2 + 4 + totalFieldSize;
        int elementSize = -1;
        Console.Out.WriteLine(Indent(indentLevel) + $"{arrayName}: ArrayProperty[{dataType.Text}, {arraySize}d]");

        if (dataType == "StructProperty")
        {
            arrayName = reader.ReadNext()!;
            dataType = reader.ReadNext();
            var structType = reader.ReadNext();
            Console.Out.WriteLine(Indent(indentLevel) + $"{arrayName}: {dataType}[{structType}]");
            for (int i = 0; i < arraySize; i++)
                ReadAndPrintStructProperty(reader, structType!, structType, indentLevel + 1);
            Console.Out.WriteLine(Indent(indentLevel) + $"[EndOf: {arrayName}]");
            Console.Out.WriteLine();
        }
        else if (dataType == "IntProperty")
            elementSize = 4;
        else if (dataType == "BoolProperty" || dataType == "ByteProperty")
            elementSize = 1;
        else if (dataType == "ObjectProperty")
        {
            for (int i = 0; i < arraySize; i++)
            {
                reader.ReadNext();
                reader.ReadNext();
            }
        }
        else
            throw new Exception($"Unsupported array element type: {dataType}");

        if (elementSize != -1)
        {
            // int arrayTotalSize = elementSize * arraySize;
            reader.SkipAfterOffset = (int) dataEnd;
        }
    }

    private static void ReadAndPrintSetProperty(TokenReader reader, string setName, int indentLevel = 0)
    {
        var dataType = reader.ReadNext();
        Console.Out.WriteLine(Indent(indentLevel) + $"{setName}: SetProperty[{dataType}]");
        if (dataType == "NameProperty")
        {
            while (true)
            {
                var name = reader.ReadNext()!;

                reader.ReadNext();
                bool setOver = reader.ReadNext()!.Text.EndsWith("Property");
                reader.Back(2);

                if (setOver)
                    break;

                Console.Out.WriteLine(Indent(indentLevel + 1) + name);
            }

            Console.Out.WriteLine(Indent(indentLevel) + $"[EndOf: {setName}]");
        }
        else throw new Exception($"Set type not supported: {dataType}");
    }

    private static void ReadAndPrintStructProperty(TokenReader reader, string structName, string? knownDataType = null, int indentLevel = 0)
    {
        var dataType = knownDataType ?? reader.ReadNext();
        Console.Out.WriteLine(Indent(indentLevel) + $"{structName}: StructProperty[{dataType}]");

        if (dataType is "Vector" or "Rotator" or "DateTime")
            return;

        while (ReadAndPrintProperty(reader, indentLevel + 1))
            continue;

        if (dataType is not ("Vector" or "Rotator" or "DateTime" or "[anonymous]"))
            Console.Out.WriteLine();
    }

    private static string Indent(int level) => new(' ', Math.Max(0, level * 4));
}