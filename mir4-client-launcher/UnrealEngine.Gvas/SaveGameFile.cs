using System.IO.Compression;
using System.Xml.Linq;
using UnrealEngine.Gvas.Exceptions;
using UnrealEngine.Gvas.FProperties;

namespace UnrealEngine.Gvas;

public class SaveGameFile
{
    public SaveGameHeader? Header { get; set; }
    public FStructProperty? Root { get; set; }
    public GvasCompressionMethod CompressionMethod { get; set; }
    
    public static SaveGameFile LoadFrom(string path, Dictionary<string, string>? typeHints = null)
    {
        using var fileStream = File.OpenRead(path);
        return Load(fileStream, typeHints);
    }

    public static SaveGameFile Load(Stream stream, Dictionary<string, string>? typeHints = null)
    {
        var reader = GetUnrealBinaryReader(stream, out var compressionMethod);

        var saveGameFile = new SaveGameFile
        {
            Header = SaveGameHeader.ReadFrom(reader),
            CompressionMethod = compressionMethod
        };

        Console.Out.WriteLine(saveGameFile.Header);

        var root = new FStructProperty();
        FProperty property;
        int i = -1;
        while (i++ > int.MinValue && (property = FProperty.ReadFrom(reader, string.Empty, typeHints: typeHints).First()) != FProperty.NoneProperty)
            root.Fields.Add(property.Name!, property);
        saveGameFile.Root = root;

        return saveGameFile;
    }

    private static BinaryReader GetUnrealBinaryReader(Stream inputStream, out GvasCompressionMethod compressionMethod)
    {
        if (inputStream.Length < 12)
            throw new SaveGameException("Not enough bytes in file, likely not a valid Gvas file");

        var tempBinaryReader = new BinaryReader(inputStream);
        var unrealHeader = tempBinaryReader.ReadUInt32();
        if (unrealHeader == 0x53415647) // GVAS, Uncompressed
        {
            inputStream.Seek(0, SeekOrigin.Begin);
            compressionMethod = GvasCompressionMethod.Uncompressed;
            return tempBinaryReader;
        }

        var compressedLength = tempBinaryReader.ReadInt32();
        var compressionMode = tempBinaryReader.ReadUInt32();

        if (compressionMode != 0x315A6C50 && compressionMode != 0x325A6C50)
            throw new SaveGameException("Unknown or invalid Gvas compression header");

        var decompressionStream = GetDecompressStream(inputStream);
        
        if (compressionMode == 0x325A6C50 && compressedLength != decompressionStream.Length)
            throw new SaveGameException("The compressed length in the header doesn't match the file's actual size");
        
        if (compressionMode == 0x325A6C50)
            decompressionStream = GetDecompressStream(decompressionStream);

        compressionMethod = compressionMode == 0x325A6C50 ? GvasCompressionMethod.DoubleZLib : GvasCompressionMethod.SingleZLib;
        return new BinaryReader(decompressionStream);
    }

    private static Stream GetDecompressStream(Stream stream)
    {
        using var decompressStream = new ZLibStream(stream, CompressionMode.Decompress, false);
        var targetStream = new MemoryStream();
        decompressStream.CopyTo(targetStream);
        targetStream.Seek(0, SeekOrigin.Begin);
        return targetStream;
    }

    public void Save(string path)
    {
        var fileStream = File.Open(path, FileMode.Create, FileAccess.ReadWrite, FileShare.Read);
        var writer = new BinaryWriter(fileStream);

        try
        {
            Header!.WriteTo(writer);
            foreach (var field in Root!.Fields)
                field.Value.WriteTo(writer);
            writer.WriteFString("None");
            writer.Write(new byte[4]);
        }
        catch (Exception ex)
        {
            Console.Out.WriteLine(ex);
        }
        finally
        {
            writer.Flush();
            writer.Close();
        }
    }

    public XElement Serialize()
    {
        var element = new XElement("SaveData");
        foreach (var property in Root!.Fields)
            element.Add(property.Value.SerializeProperty());
        return element;
    }
}