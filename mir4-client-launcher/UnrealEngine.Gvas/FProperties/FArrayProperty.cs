using UnrealEngine.Gvas.Exceptions;

namespace UnrealEngine.Gvas.FProperties;

public class FArrayProperty : FProperty
{
    public string? ElementType { get; set; }
    public List<FProperty> Elements { get; } = new();

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        ElementType = reader.ReadFString();
        var elementType = FindPropertyTypeByName(ElementType);
        reader.ReadByte(); // Unknown

        int elementCount = reader.ReadInt32();
        if (elementCount == 0)
            return;

        if (ElementType == "StructProperty")
        {
            var structProperties = ReadFrom(reader, path, elementCount).Cast<FStructProperty>().ToList();
            Elements.AddRange(structProperties);

            foreach (var structProperty in structProperties)
                structProperty.TypeName = structProperties[0].TypeName;
        }
        else if (ElementType == "NameProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FNameProperty();
                elementInstance.Read(reader, propertyName, 0, path);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "IntProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FIntProperty();
                elementInstance.Read(reader, propertyName, 0, path);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "BoolProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FBoolProperty();
                elementInstance.Read(reader, propertyName, 0, path, null, true);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "ByteProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FByteProperty();
                elementInstance.Read(reader, propertyName, 0, path, null, true);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "StrProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FStrProperty();
                elementInstance.Read(reader, propertyName, 0, path, null, true);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "ObjectProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FObjectProperty();
                elementInstance.Read(reader, propertyName, 0, path, null, true);
                Elements.Add(elementInstance);
            }
        }
        else if (ElementType == "FloatProperty")
        {
            for (int i = 0; i < elementCount; i++)
            {
                var elementInstance = new FFloatProperty();
                elementInstance.Read(reader, propertyName, 0, path, null, true);
                Elements.Add(elementInstance);
            }
        }
        else
            throw new SaveGameException($"Unsupported {nameof(FArrayProperty)} type: '{ElementType}'");
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.WriteFString(ElementType);
        writer.Write((byte) 0);

        writer.Write(Elements.Count);

        if (ElementType == "StructProperty")
        {
            int i = 0;
            foreach (var property in Elements)
                property.WriteTo(writer, i++ == 0);
        } 
        else if (ElementType is "IntProperty" or "BoolProperty" or "ByteProperty" or "NameProperty" or "StrProperty" or "FloatProperty")
        {
            foreach (var property in Elements)
                property.WriteTo(writer, false);
        }
        else throw new Exception($"FArrayProperty type {ElementType} is not implemented!");
    }

    protected override IEnumerable<object> SerializeContent()
    {
        foreach (var element in Elements)
            yield return element.SerializeProperty();
    }
}