namespace UnrealEngine.Gvas.FProperties;

[OptionalGuid]
public class FSoftObjectProperty : FProperty
{
    public string? ObjectName { get; set; }
    public int Unknown1 { get; set; }

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        ObjectName = reader.ReadFString();
        Unknown1 = reader.ReadInt32();
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.WriteFString(ObjectName!);
        writer.Write(Unknown1);
    }

    protected override IEnumerable<object> SerializeContent()
    {
        yield return ObjectName ?? string.Empty;
    }
}
