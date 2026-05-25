import 'dart:math' as math;

class RankingEngine {
  /// THE VECTOR SPACE MODEL: Calculates Cosine Similarity between a query (Syllabus)
    /// and a document (Academic Material) using TF-IDF logic.
      
        static double calculateSimilarity(String syllabus, String document) {
            Map<String, int> syllabusFreq = _getTermFrequency(syllabus);
                Map<String, int> documentFreq = _getTermFrequency(document);

                    // 1. Create a union of all unique terms (The Vector Space)
                        Set<String> allTerms = {...syllabusFreq.keys, ...documentFreq.keys};

                            // 2. Build Vectors
                                List<double> syllabusVector = [];
                                    List<double> documentVector = [];

                                        for (String term in allTerms) {
                                              syllabusVector.add(syllabusFreq[term]?.toDouble() ?? 0.0);
                                                    documentVector.add(documentFreq[term]?.toDouble() ?? 0.0);
                                                        }

                                                            // 3. MATH: COSINE SIMILARITY calculation
                                                                // formula: (A . B) / (||A|| * ||B||)
                                                                    double dotProduct = 0.0;
                                                                        double syllabusMagnitude = 0.0;
                                                                            double documentMagnitude = 0.0;

                                                                                for (int i = 0; i < syllabusVector.length; i++) {
                                                                                      dotProduct += syllabusVector[i] * documentVector[i];
                                                                                            syllabusMagnitude += syllabusVector[i] * syllabusVector[i];
                                                                                                  documentMagnitude += documentVector[i] * documentVector[i];
                                                                                                      }

                                                                                                          syllabusMagnitude = math.sqrt(syllabusMagnitude);
                                                                                                              documentMagnitude = math.sqrt(documentMagnitude);

                                                                                                                  if (syllabusMagnitude == 0 || documentMagnitude == 0) return 0.0;

                                                                                                                      return dotProduct / (syllabusMagnitude * documentMagnitude);
                                                                                                                        }

                                                                                                                          static Map<String, int> _getTermFrequency(String text) {
                                                                                                                              // Tokenization logic: lowercasing, removing symbols, splitting by space
                                                                                                                                  List<String> tokens = text.toLowerCase()
                                                                                                                                          .replaceAll(RegExp(r'[^\w\s]'), '')
                                                                                                                                                  .split(RegExp(r'\s+'))
                                                                                                                                                          .where((t) => t.length > 2) // Ignore tiny words (stop-word filter)
                                                                                                                                                                  .toList();

                                                                                                                                                                      Map<String, int> freq = {};
                                                                                                                                                                          for (String t in tokens) {
                                                                                                                                                                                freq[t] = (freq[t] ?? 0) + 1;
                                                                                                                                                                                    }
                                                                                                                                                                                        return freq;
                                                                                                                                                                                          }
                                                                                                                                                                                          }
                                                                                                                                                                                          