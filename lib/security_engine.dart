import 'dart:io';
import 'dart:typed_data';
import 'package:encrypt/encrypt.dart' as encrypt;
import 'package:path_provider/path_provider.dart';
import 'package:archive/archive.dart';
import 'package:image/image.dart' as img;

class SteganoEngine {
  static final _key = encrypt.Key.fromUtf8('GENERIC_SECRET_KEY_1234567890123');
  static final _iv = encrypt.IV.fromLength(16);
  static final _encrypter = encrypt.Encrypter(encrypt.AES(_key));

  /// THE BINARY WEAVER: Advanced LSB Steganography.
  /// Hides encrypted PDF data inside the least significant bits of an image.
  static Future<File> weaveKnowledge({
    required File carrierImage,
    required File payloadDoc,
  }) async {
    // 1. COMPRESS & ENCRYPT PAYLOAD
    final docBytes = await payloadDoc.readAsBytes();
    final compressedBytes = GZipEncoder().encode(docBytes);
    final encryptedData = _encrypter.encryptBytes(compressedBytes!, iv: _iv).bytes;

    // 2. LOAD CARRIER IMAGE
    final image = img.decodeImage(await carrierImage.readAsBytes());
    if (image == null) {
      throw Exception("INVALID_CARRIER_FORMAT: The selected image format (likely HEIC/HEIF) is not supported for pixel-weaving. Please use a standard PNG or JPG.");
    }

    // 3. THE WEAVING LOGIC
    Uint8List dataToHide = Uint8List(4 + encryptedData.length);
    ByteData.view(dataToHide.buffer).setUint32(0, encryptedData.length);
    dataToHide.setRange(4, dataToHide.length, encryptedData);

    // CAPACITY CHECK: 1 byte of payload needs 8 bits (3 pixels * 3 channels = 9 bits per iteration)
    // Roughly 1 pixel stores 0.375 bytes.
    if (dataToHide.length * 8 > image.width * image.height * 3) {
      throw Exception("IMAGE_TOO_SMALL: The selected carrier image does not have enough pixel-density to hide this document. Please use a higher resolution image.");
    }

    int bitIndex = 0;
    for (int y = 0; y < image.height; y++) {
      for (int x = 0; x < image.width; x++) {
        if (bitIndex >= dataToHide.length * 8) break;

        final pixel = image.getPixel(x, y);
        int r = pixel.r.toInt();
        int g = pixel.g.toInt();
        int b = pixel.b.toInt();

        if (bitIndex < dataToHide.length * 8) r = (r & 0xFE) | _getBit(dataToHide, bitIndex++);
        if (bitIndex < dataToHide.length * 8) g = (g & 0xFE) | _getBit(dataToHide, bitIndex++);
        if (bitIndex < dataToHide.length * 8) b = (b & 0xFE) | _getBit(dataToHide, bitIndex++);

        image.setPixelRgb(x, y, r, g, b);
      }
    }

    final directory = await getApplicationDocumentsDirectory();
    final path = '${directory.path}/SHELL_${DateTime.now().millisecondsSinceEpoch}.png';
    final shellFile = File(path);
    await shellFile.writeAsBytes(img.encodePng(image));

    return shellFile;
  }

  static int _getBit(Uint8List data, int bitIndex) {
    int byteIndex = bitIndex ~/ 8;
    int bitOffset = 7 - (bitIndex % 8);
    return (data[byteIndex] >> bitOffset) & 1;
  }

  /// RECONSTRUCTION LOGIC: Extracts and reassembles knowledge from binary bits.
  static Future<Uint8List?> extractKnowledge(File vaultFile) async {
    final image = img.decodeImage(await vaultFile.readAsBytes());
    if (image == null) return null;

    int bitIndex = 0;

    // 1. EXTRACT LENGTH (32 bits)
    Uint8List lengthBuffer = Uint8List(4);
    for (int y = 0; y < image.height && bitIndex < 32; y++) {
      for (int x = 0; x < image.width && bitIndex < 32; x++) {
        final pixel = image.getPixel(x, y);
        if (bitIndex < 32) _setBit(lengthBuffer, bitIndex++, pixel.r.toInt() & 1);
        if (bitIndex < 32) _setBit(lengthBuffer, bitIndex++, pixel.g.toInt() & 1);
        if (bitIndex < 32) _setBit(lengthBuffer, bitIndex++, pixel.b.toInt() & 1);
      }
    }

    int dataLength = ByteData.view(lengthBuffer.buffer).getUint32(0);
    
    // 2. EXTRACT ENCRYPTED PAYLOAD
    Uint8List encryptedData = Uint8List(dataLength);
    int globalBitIndex = 0;
    int payloadBitStart = 32;
    int payloadBitEnd = 32 + (dataLength * 8);

    for (int y = 0; y < image.height && globalBitIndex < payloadBitEnd; y++) {
      for (int x = 0; x < image.width && globalBitIndex < payloadBitEnd; x++) {
        final pixel = image.getPixel(x, y);
        
        void process(int chan) {
          if (globalBitIndex >= payloadBitStart && globalBitIndex < payloadBitEnd) {
            _setBit(encryptedData, globalBitIndex - payloadBitStart, chan & 1);
          }
          globalBitIndex++;
        }
        process(pixel.r.toInt());
        process(pixel.g.toInt());
        process(pixel.b.toInt());
      }
    }

    try {
      final decrypted = _encrypter.decryptBytes(encrypt.Encrypted(encryptedData), iv: _iv);
      final decompressed = GZipDecoder().decodeBytes(decrypted);
      return Uint8List.fromList(decompressed);
    } catch (e) {
      return null;
    }
  }

  /// THE SHREDDER: Breaks the woven data into mathematical segments for P2P distribution.
  static List<Uint8List> shardKnowledge(Uint8List fullData, {int shardCount = 5}) {
    int segmentSize = (fullData.length / shardCount).ceil();
    List<Uint8List> shards = [];

    for (int i = 0; i < shardCount; i++) {
      int start = i * segmentSize;
      int end = (i + 1) * segmentSize;
      if (end > fullData.length) end = fullData.length;

      final rawPayload = fullData.sublist(start, end);
      
      // ADD MATHEMATICAL INTEGRITY (CRC-style simple checksum)
      // Header: [SHARD_INDEX (1 byte)] + [PAYLOAD_LENGTH (4 bytes)] + [CHECKSUM (4 bytes)]
      final header = ByteData(9);
      header.setUint8(0, i);
      header.setUint32(1, rawPayload.length);
      header.setUint32(5, _calculateIntegrity(rawPayload));

      final shard = BytesBuilder();
      shard.add(header.buffer.asUint8List());
      shard.add(rawPayload);
      shards.add(shard.toBytes());
    }
    return shards;
  }

  static int _calculateIntegrity(Uint8List data) {
    // Simple checksum algorithm for prototype perfection
    int checksum = 0;
    for (var byte in data) {
      checksum = (checksum + byte) % 0xFFFFFFFF;
    }
    return checksum;
  }

  /// THE REASSEMBLER: Verifies and stitches shards back into the Knowledge DNA.
  static Uint8List? verifyAndStitch(List<Uint8List> shards, int expectedCount) {
    if (shards.length < expectedCount) return null;

    // Sort by shard index
    shards.sort((a, b) => a[0].compareTo(b[0]));

    final result = BytesBuilder();
    for (var shard in shards) {
      final view = ByteData.sublistView(shard);
      final length = view.getUint32(1);
      final storedChecksum = view.getUint32(5);
      final payload = shard.sublist(9);

      // MATHEMATICAL VERIFICATION
      if (payload.length != length || _calculateIntegrity(payload) != storedChecksum) {
        throw Exception("DATA_CORRUPTION_DETECTED_IN_SHARD");
      }
      result.add(payload);
    }
    return result.toBytes();
  }

  static void _setBit(Uint8List data, int bitIndex, int bitValue) {
    int byteIndex = bitIndex ~/ 8;
    int bitOffset = 7 - (bitIndex % 8);
    if (bitValue == 1) {
      data[byteIndex] |= (1 << bitOffset);
    } else {
      data[byteIndex] &= ~(1 << bitOffset);
    }
  }
}
