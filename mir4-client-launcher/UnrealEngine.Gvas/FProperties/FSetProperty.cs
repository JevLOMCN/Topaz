using UnrealEngine.Gvas.Exceptions;

namespace UnrealEngine.Gvas.FProperties;

public class FSetProperty : FProperty
{
    public string? ElementType { get; set; }
    public List<FProperty> Elements { get; } = new();

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        ElementType = reader.ReadFString();
        var elementType = FindPropertyTypeByName(ElementType);
        reader.ReadBytes(5); // Unknown

        if (elementType == null)
            throw new SaveGameException($"Invalid {nameof(FSetProperty)} element type: '{ElementType}'.");

        var elementCount = reader.ReadInt32();
        for (int i = 0; i < elementCount; i++)
        {
            var elementInstance = InstantiateType(elementType);
            elementInstance.Read(reader, null, 0, path);
            Elements.Add(elementInstance);
        }
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.WriteFString(ElementType);
        writer.Write(new byte[5]);

        writer.Write(Elements.Count);
        foreach (var element in Elements)
            element.Write(writer, skipHeader);
    }

    protected override IEnumerable<object> SerializeContent()
    {
        foreach (var element in Elements)
            yield return element.SerializeProperty();
    }
}