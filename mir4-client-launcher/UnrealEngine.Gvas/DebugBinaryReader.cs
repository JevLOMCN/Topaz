using System.Text;

namespace UnrealEngine.Gvas;

public class DebugBinaryReader : BinaryReader
{
    public long Position => BaseStream.Position;
    
    public DebugBinaryReader(Stream input) : base(input)
    {
    }

    public DebugBinaryReader(Stream input, Encoding encoding) : base(input, encoding)
    {
    }

    public DebugBinaryReader(Stream input, Encoding encoding, bool leaveOpen) : base(input, encoding, leaveOpen)
    {
    }
}