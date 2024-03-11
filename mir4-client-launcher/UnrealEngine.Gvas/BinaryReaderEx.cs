using System.Text;
using UnrealEngine.Gvas.Exceptions;

namespace UnrealEngine.Gvas;

public static class BinaryReaderEx
{
    public static string? ReadFString(this BinaryReader reader)
    {
        int length = reader.ReadInt32();
        if (length is > 512 or < -512)
            throw new SaveGameException($"Sanity check failed: FString length is {length}");

        if (length is < 0)
            return Encoding.Unicode.GetString(reader.ReadBytes(length * -2)).TrimEnd('\0');
        return Encoding.ASCII.GetString(reader.ReadBytes(length)).TrimEnd('\0');
    }

    public static void WriteFString(this BinaryWriter writer, string? str)
    {
        if (str is {Length: > 0})
        {
            if (Encoding.UTF8.GetByteCount(str) == str.Length)
            {
                // safe to use 7-bit ASCII encoding
                writer.Write(str!.Length + 1);
                writer.Write(str.ToCharArray());
                writer.Write((byte) 0);
            }
            else
            {
                // use UTF-16 LE encoding
                writer.Write(-1 - Encoding.Unicode.GetByteCount(str)/2);
                writer.Write(Encoding.Unicode.GetBytes(str));
                writer.Write((short) 0);
            }
        }
        else
        {
            writer.Write(0);
        }
    }
}