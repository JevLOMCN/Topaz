namespace UnrealEngine.Gvas.FProperties;

[OptionalGuid]
public class FInt64Property : FProperty
{
    public long Value { get; set; }

    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        Value = reader.ReadInt64();
    }
    
    internal override void Write(BinaryWriter writer, bool skipHeader)
    {
        writer.Write(Value);
    }
    
    protected override IEnumerable<object> SerializeContent()
    {
        yield return Value;
    }
    
    public override object AsPrimitive() => Value;
    
    public override void SetValue(object? val) => Value = (long) val;
}