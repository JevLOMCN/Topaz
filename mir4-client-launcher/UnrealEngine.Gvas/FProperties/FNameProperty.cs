namespace UnrealEngine.Gvas.FProperties;

[OptionalGuid]
public class FNameProperty : FProperty
{
    public string? Value { get; set; }

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        Value = reader.ReadFString();
    }

    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.WriteFString(Value);
    }

    protected override IEnumerable<object> SerializeContent()
    {
        yield return Value ?? string.Empty;
    }

    public override object? AsPrimitive() => Value;

    public override void SetValue(object? val) => Value = (string?) val;
}