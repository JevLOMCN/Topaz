namespace UnrealEngine.Gvas.FProperties;

public class FGuidProperty : FProperty
{
    internal override void Read(BinaryReader reader, string? propertyName, long fieldLength, string path, string? typeNameHint = null, bool bodyOnly = false, Dictionary<string, string>? typeHints = null)
    {
        throw new NotImplementedException();
    }

    protected override IEnumerable<object> SerializeContent()
    {
        throw new NotImplementedException();
    }
}