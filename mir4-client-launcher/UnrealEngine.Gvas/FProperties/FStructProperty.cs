using System.Diagnostics;
using System.Xml.Linq;

namespace UnrealEngine.Gvas.FProperties;

public class FStructProperty : FProperty
{
    private Guid structureGuid = System.Guid.Empty;
    private Guid? optionalId;

    public string? TypeName { get; set; }
    public Dictionary<string, FProperty> Fields { get; } = new();

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        if (!bodyOnly)
        {
            TypeName = reader.ReadFString();
            structureGuid = new Guid(reader.ReadBytes(16));
            if (reader.ReadBytes(1)[0] != 0)
                optionalId = new Guid(reader.ReadBytes(16));
            if (fieldLength == 0)
                return;
        }

        if (TypeName == "Vector")
        {
            int componentSize = (int)fieldLength / 3;
            Fields.Add("X", ReadFloatComponent(reader, componentSize, "X"));
            Fields.Add("Y", ReadFloatComponent(reader, componentSize, "Y"));
            Fields.Add("Z", ReadFloatComponent(reader, componentSize, "Z"));
        }
        else if (TypeName == "Rotator")
        {
            int componentSize = (int)fieldLength / 3;
            Fields.Add("Pitch", ReadFloatComponent(reader, componentSize, "Pitch"));
            Fields.Add("Yaw", ReadFloatComponent(reader, componentSize, "Yaw"));
            Fields.Add("Roll", ReadFloatComponent(reader, componentSize, "Roll"));
        }
        else if (TypeName == "Quat")
        {
            int componentSize = (int)fieldLength / 4;
            Fields.Add("X", ReadFloatComponent(reader, componentSize, "X"));
            Fields.Add("Y", ReadFloatComponent(reader, componentSize, "Y"));
            Fields.Add("Z", ReadFloatComponent(reader, componentSize, "Z"));
            Fields.Add("W", ReadFloatComponent(reader, componentSize, "W"));
        }
        else if (TypeName == "LinearColor")
        {
            Fields.Add("R", new FFloatProperty { Name = "R", Value = reader.ReadSingle() }); // Keep as float!
            Fields.Add("G", new FFloatProperty { Name = "G", Value = reader.ReadSingle() });
            Fields.Add("B", new FFloatProperty { Name = "B", Value = reader.ReadSingle() });
            Fields.Add("A", new FFloatProperty { Name = "A", Value = reader.ReadSingle() });
        }
        else if (TypeName == "DateTime")
            Fields.Add("Ticks", new FInt64Property { Name = "Ticks", Value = reader.ReadInt64() });
        else if (TypeName == "IntPoint")
        {
            Fields.Add("X", new FIntProperty { Name = "X", Value = reader.ReadInt32() });
            Fields.Add("Y", new FIntProperty { Name = "Y", Value = reader.ReadInt32() });
        }
        else if ((TypeName ?? typeNameHint) == "Guid")
        {
            if (!bodyOnly || true)
            {
                Fields.Add("A", new FIntProperty { Name = "A", Value = reader.ReadInt32() });
                Fields.Add("B", new FIntProperty { Name = "B", Value = reader.ReadInt32() });
                Fields.Add("C", new FIntProperty { Name = "C", Value = reader.ReadInt32() });
                Fields.Add("D", new FIntProperty { Name = "D", Value = reader.ReadInt32() });
            }
        }
        else
        {
            FProperty? field;
            while ((field = ReadFrom(reader, path, typeHints: typeHints).First()) != NoneProperty)
                Fields.TryAdd(field.Name!, field);
        }
    }

    private FFloatProperty ReadFloatComponent(BinaryReader reader, int componentSize, string componentName) => new()
    {
        Name = componentName, 
        Value = componentSize == 4 ? reader.ReadSingle() : reader.ReadDouble(),
        IsDouble = componentSize == 8
    };

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        if (!skipHeader)
        {
            writer.WriteFString(TypeName);
            writer.Write(structureGuid.ToByteArray());
            writer.Write((byte)(optionalId != null ? 1 : 0));
            if(optionalId != null)
                writer.Write(optionalId.Value.ToByteArray());
        }

        if (TypeName == "Vector")
        {
            WriteFloatComponent(writer, "X");
            WriteFloatComponent(writer, "Y");
            WriteFloatComponent(writer, "Z");
        }
        else if (TypeName == "Rotator")
        {
            WriteFloatComponent(writer, "Pitch");
            WriteFloatComponent(writer, "Yaw");
            WriteFloatComponent(writer, "Roll");
        }
        else if (TypeName == "Quat")
        {
            WriteFloatComponent(writer, "X");
            WriteFloatComponent(writer, "Y");
            WriteFloatComponent(writer, "Z");
            WriteFloatComponent(writer, "W");
        }
        else if (TypeName == "LinearColor")
        {
            writer.Write((Fields["R"] as FFloatProperty)!.Value);
            writer.Write((Fields["G"] as FFloatProperty)!.Value);
            writer.Write((Fields["B"] as FFloatProperty)!.Value);
            writer.Write((Fields["A"] as FFloatProperty)!.Value);
        }
        else if (TypeName == "DateTime")
            writer.Write((Fields["Ticks"] as FInt64Property)!.Value);
        else if (TypeName == "IntPoint")
        {
            writer.Write((Fields["X"] as FIntProperty)!.Value);
            writer.Write((Fields["Y"] as FIntProperty)!.Value);
        }
        else if (TypeName == "Guid")
        {
            writer.Write((Fields["A"] as FIntProperty)!.Value);
            writer.Write((Fields["B"] as FIntProperty)!.Value);
            writer.Write((Fields["C"] as FIntProperty)!.Value);
            writer.Write((Fields["D"] as FIntProperty)!.Value);
        }
        else
        {
            foreach (var (_, field) in Fields)
                field.WriteTo(writer);
            writer.WriteFString("None");
        }
    }
    
    private void WriteFloatComponent(BinaryWriter writer, string componentName)
    {
        var property = (Fields[componentName] as FFloatProperty)!;
        if(property.IsDouble)
            writer.Write(property.Value);
        else
            writer.Write((float)property.Value);
    }

    protected override IEnumerable<object> SerializeContent()
    {
        foreach (var (_, field) in Fields)
            yield return field.SerializeProperty();
    }

    protected override void ModifyXmlNode(XElement element)
    {
        element.SetAttributeValue("Type", TypeName);
    }
}