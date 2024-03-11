using System.Diagnostics;
using System.Xml.Linq;
using UnrealEngine.Gvas.Exceptions;

namespace UnrealEngine.Gvas.FProperties;

public class FMapProperty : FProperty
{
    public Type? KeyType { get; private set; }
    public Type? ValueType { get; private set; }
    public Dictionary<FProperty, FProperty> KeyValuePairs { get; } = new();

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        var keyTypeName = reader.ReadFString();
        var valueTypeName = reader.ReadFString();

        KeyType = FindPropertyTypeByName(keyTypeName);
        ValueType = FindPropertyTypeByName(valueTypeName);

        if (KeyType == null)
            throw new SaveGameException($"The {nameof(FMapProperty)}'s key type cannot be null.");
        if (ValueType == null)
            throw new SaveGameException($"The {nameof(FMapProperty)}'s key type cannot be null.");

        var temp = reader.ReadBytes(5);
        int elementCount = reader.ReadInt32();
        for (int i = 0; i < elementCount; i++)
        {
            var keyInstance = InstantiateType(KeyType);
            var valueInstance = InstantiateType(ValueType);

            var keyPath = $"{path}.Key";
            var valuePath = $"{path}.Value";

            var keyTypeHint = "Guid";
            var valueTypeHint = "StructProperty";

            typeHints?.TryGetValue(keyPath, out keyTypeHint);
            typeHints?.TryGetValue(valuePath, out valueTypeHint);

            keyInstance.Read(reader, null, 0, keyPath, keyTypeHint, true, typeHints: typeHints);
            valueInstance.Read(reader, null, 0, valuePath, valueTypeHint, true, typeHints: typeHints);

            KeyValuePairs.Add(keyInstance, valueInstance);
        }
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.WriteFString(KeyType!.Name.Remove(0, 1));
        writer.WriteFString(ValueType!.Name.Remove(0, 1));

        writer.Write(new byte[5]);
        writer.Write(KeyValuePairs.Count);
        
        foreach (var (key, value) in KeyValuePairs)
        {
            key.Write(writer, true);
            value.Write(writer, true);
        }
    }

    protected override IEnumerable<object> SerializeContent()
    {
        foreach (var (key, value) in KeyValuePairs)
        {
            var element = new XElement("KeyValuePair");
            element.Add(new XElement("Key", key.SerializeProperty()));
            element.Add(new XElement("Value", value.SerializeProperty()));
            yield return element;
        }
    }

    protected override void ModifyXmlNode(XElement element)
    {
        element.SetAttributeValue("KeyType", KeyType?.Name);
        element.SetAttributeValue("ValueType", ValueType?.Name);
    }
}