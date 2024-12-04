/*
===========================================================================
PROJECT: Direct-Mapped Write-Back Cache [Trace Driven Simulation]
===========================================================================
NAME: Tyler Neal
USER ID: tpneal
DATE: 04/17/2024
FILE NAME: cache.c
PROGRAM PURPOSE:
    This file contains the initialization logic for the various structures 
    required to model a direct-mapped write-back cache. This includes 
    structures for cache blocks, sets, and the complete cache.
===========================================================================
*/

#include "cache.h"

int cache_layers = 0;

/**
 * @brief Simulates a (1-3) layer direct-mapped write-back cache and prints statistics given inputed tracer files
 * 
 * @param argc 
 * @param argv 
 * @return int 
 */
int main(int argc, char *argv[]) {
    clock_t start_time, end_time;   // Declare variables for timing
    double elapsed_time;
    start_time = clock();           // Record the start time

    // Verify command-line arguments
    if (argc < 8) {
        fprintf(stderr, "\nUsage: %s <cache_type> <line_size> <cache_layers> <L1_size> <L2_size> <L3_size> <print_style>\n", argv[0]);
        exit(EXIT_FAILURE);
    }

    // Parse command-line arguments
    char cache_type = *(char*)argv[1];
    int line_size = atoi(argv[2]) * 4;
    cache_layers = atoi(argv[3]);
    int print_style = atoi(argv[7]);

    // Initialize cache layers
    Cache* cache[3];
    Cache *L1, *L2, *L3;
    int L1_size = atoi(argv[4]) * 1024;  // Total size of L1 cache in bytes
    int L2_size = atoi(argv[5]) * 1024;  // Total size of L2 cache in bytes
    int L3_size = atoi(argv[6]) * 1024;  // Total size of L3 cache in bytes

    // Setup L1
    L1 = setupCache(1, L1_size, line_size);
    cache[0] = L1;

    // Setup L2
    if (cache_layers > 1) {
        L2 = setupCache(2, L2_size, line_size);
        cache[1] = L2;
    }

    // Setup L3
    if (cache_layers > 2) {
        L3 = setupCache(3, L3_size, line_size);
        cache[2] = L3;
    }

    // Process requests until end of file
    char ch;
    bool data_found = false;
    char buffer[11];
    buffer[10] = '\0';
    Request* request = createRequest();

    while ((ch = getchar()) != EOF) {
        if (ch == '@') {  // Tracer found
            fgets(buffer, sizeof(buffer), stdin);  // Reads trace format: @<I/D><R/W><hex-address>
            for (int i = 0; i < cache_layers; i++) {  // Iterate layers until hit
                formatRequest(request, cache[i], buffer);
                if (request->ref_type == cache_type || cache_type == 'U') {
                    cache[i]->requests++;
                    processRequest(request, cache[i], &data_found);  // Process request in appropriate cache
                    if (data_found) break;  // Break if hit was found
                }
            }
            data_found = false;
        }
    }

    // Calculate miss rates
    float miss_rates[3];
    for (int i = 0; i < cache_layers; i++) {
        miss_rates[i] = ((float)cache[i]->misses / (float)cache[i]->requests)   ;
    }

    // Print cache statistics
    for (int i = 0; i < cache_layers; i++) {
        printCacheStats(cache[i], print_style);
    }
    printf("------------------------------------------------------------\n");
    if (cache_layers == 1) {
        printf("AMAT: %.2f\n", (HIT_TIME_L1 + (miss_rates[0] * MEM_ACCESS_TIME)));
    }
    if (cache_layers == 2) {
        printf("AMAT: %.2f\n", (HIT_TIME_L1 + (miss_rates[0] * (HIT_TIME_L2 + (miss_rates[1] * MEM_ACCESS_TIME)))));
    }
    if (cache_layers == 3) {
        printf("AMAT: %.2f\n", (HIT_TIME_L1 + (miss_rates[0] * (HIT_TIME_L2 + (miss_rates[1] * HIT_TIME_L3 + (miss_rates[2] * MEM_ACCESS_TIME))))));
    }
    printf("------------------------------------------------------------\n");

    // Clean up memory
    destroyRequest(request);
    for (int i = 0; i < cache_layers; i++) {
        destroyCache(cache[i]);
    }

    end_time = clock();  // Record the end time
    elapsed_time = ((double)(end_time - start_time)) / CLOCKS_PER_SEC;  // Calculate elapsed time in seconds
    printf("Total Elapsed Time: %.2f seconds\n", elapsed_time);

    return 0; // End of program
}

/**
 * @brief Allocates memory for a cache and returns a pointer to it
 * 
 * @param cache_size 
 * @param line_size 
 * @param layer 
 * @return Cache* 
 */
Cache* constructCache(int cache_size, int line_size, int layer) {
    // Variables
    Cache* cache;

    // Allocate space for cache
    cache = (Cache*)malloc(sizeof(Cache));
    if (cache == NULL) {
        fprintf(stderr, "Failed to allocate memory for cache.\n");
        exit(EXIT_FAILURE);
    }

    // Initialize cache struct values
    cache->cache_size = cache_size;
    cache->line_size = line_size;
    cache->num_lines = (cache_size / line_size);
    cache->layer = layer;
    cache->requests = 0;
    cache->hits = 0;
    cache->misses = 0;
    cache->read_to_write = 0;
    cache->write_to_write = 0;

    // Memory allocation for cache lines
    cache->lines = (Line*)malloc(cache->num_lines * sizeof(Line));
    if (cache->lines == NULL) {
        fprintf(stderr, "Failed to allocate memory for cache lines.\n");
        exit(EXIT_FAILURE);
    }
    for (int i = 0; i < cache->num_lines; i++) {
        // Setting initial values for each cache line
        cache->lines[i].dirty = 0;        // Marks line as initially clean (not modified)
        cache->lines[i].tag[0] = 'x';     // Initial tag set to 'x' to represent uninitialized
    }

    if (DEBUG) {
        printf("Cache Created\n..............\nSize: %d bytes\nLine Count: %d\nLine Size: %d bytes\n", 
               cache->cache_size, cache->num_lines, cache->line_size);
    }

    return cache;
}

/**
 * @brief Deallocates memory for a cache given its pointer
 * 
 * @param cache 
 */
void destroyCache(Cache* cache) {
    if (cache != NULL) {
        if (cache->lines != NULL) {
            free(cache->lines);
        }
        free(cache);
    }

    if (DEBUG) {
        printf("Cache successfully deleted\n");
    }
}

/**
 * @brief Cacluates and stores the address field sizes for a given cache
 * 
 * @param layer 
 * @param cache_size 
 * @param line_size 
 * @return Cache* 
 */
Cache* setupCache(int layer, int cache_size, int line_size) {
    Cache* cache = constructCache(cache_size, line_size, layer);

    // Calculate Address Field Sizes
    cache->offset_size = (int)ceil(log2(line_size));
    cache->index_size = (int)ceil(log2(cache_size / line_size));
    cache->tag_size = INSTRUCTION_SIZE - cache->index_size - cache->offset_size;

    // Debug Printing
    if (DEBUG) {
        printf("\nCache Size: %d\nLine Size: %d\ncache->tag_size: %d\ncache->index_size: %d\ncache->offset_size: %d\n\n",
               cache_size, line_size, cache->tag_size, cache->index_size, cache->offset_size);
    }

    return cache;
}

/**
 * @brief Allocates memory for a memory request
 * 
 * @return Request* 
 */
Request* createRequest() {
    // Allocate memory for Request
    Request* request = (Request*)malloc(sizeof(Request));
    if (request == NULL) {
        fprintf(stderr, "Failed to allocate memory for request.\n");
        exit(EXIT_FAILURE);
    }

    return request;
}

/**
 * @brief Deallocates memory for a request given its pointer
 * 
 * @param request 
 */
void destroyRequest(Request* request) {
    if (request != NULL) {
        free(request);
    }
}

/**
 * @brief Formats the tag index and offset of a request given the cache it will query
 * 
 * @param request 
 * @param cache 
 * @param buffer 
 */
void formatRequest(Request* request, Cache* cache, char* buffer) {
    /* Assign Characteristics Based on Tracer */
    if (buffer[0] == 'I') request->ref_type = 'I';
    else if (buffer[0] == 'D') request->ref_type = 'D';
    else {
        printf("Error: invalid request reference type of %c.\n", buffer[0]);
        exit(EXIT_FAILURE);
    }

    if (buffer[1] == 'R') request->access_type = 'R';
    else if (buffer[1] == 'W') request->access_type = 'W';
    else {
        printf("Error: invalid request access type of %c.\n", buffer[1]);
        exit(EXIT_FAILURE);
    }

    // Fill in hex address
    sscanf(buffer + 2, "%x", &request->address);

    // Convert address to binary representation (placeholder function itob)
    char binary[33];
    char* tmp = itob(request->address);
    strcpy(binary, tmp);
    free(tmp);

    // Retrieve and copy tag, index, and offset
    strncpy(request->tag, binary, cache->tag_size);
    request->tag[cache->tag_size] = '\0';
    strncpy(request->index, binary + cache->tag_size, cache->index_size);
    request->index[cache->index_size] = '\0';
    strncpy(request->offset, binary + cache->tag_size + cache->index_size, cache->offset_size);
    request->offset[cache->offset_size] = '\0';
}

/**
 * @brief Takes a request and sends the read / write request to the supplied cache
 * 
 * @param request 
 * @param cache 
 * @param data_found 
 */
void processRequest(Request* request, Cache* cache, bool* data_found) {
    if (cache == NULL || request == NULL) {
        printf("Error: must operate on a valid cache and request.\n");
        exit(EXIT_FAILURE);
    }

    if (request->access_type == 'R') {
        readData(cache, request, data_found);
    } else if (request->access_type == 'W') {
        writeData(cache, request, data_found);
    } else {
        printf("Error: invalid request access type during processRequest().\n");
        exit(EXIT_FAILURE);
    }
}

/**
 * @brief Reads data from a cache at the address specified in the request
 * 
 * @param cache 
 * @param request 
 * @param data_found 
 */
void readData(Cache* cache, Request* request, bool* data_found) {
    int index = btoi(request->index);
    if (index >= 0 && index <= cache->num_lines) {
        Line* line = &cache->lines[index];

        // Check if the tag matches
        if (strcmp(line->tag, request->tag) == 0) {
            cache->hits++;
            *data_found = true;
        } else {
            cache->misses++; 
            if (line->dirty == 1) {
                cache->read_to_write++;
            }

            // Load the new tag, mark as clean
            strcpy(line->tag, request->tag);
            line->dirty = 0;
        }
    }
}

/**
 * @brief Writes data from a cache at the address specified in the request
 * 
 * @param cache 
 * @param request 
 * @param data_found 
 */
void writeData(Cache* cache, Request* request, bool* data_found) {
    int index = btoi(request->index);
    if (index >= 0 && index <= cache->num_lines) {
        Line* line = &cache->lines[index];

        // If line found in cache
        if (strcmp(line->tag, request->tag) == 0) {
            cache->hits++;
            line->dirty = 1;  // Data is now modified
            *data_found = true;
        } else {
            cache->misses++;
            if (line->dirty == 1) {
                cache->write_to_write++;
            }

            // Load the new tag, mark as clean
            strcpy(line->tag, request->tag);
            line->dirty = 1;
        }
    }
}

/**
 * @brief Prints cache statistics (style = 1: print for project part 1) (style = 1: print for project part 2)
 * 
 * @param cache 
 * @param style 
 */
void printCacheStats(Cache* cache, int style) {
    if (cache == NULL) {
        printf("Error: must operate on a valid cache\n");
        exit(EXIT_FAILURE);
    }

    if (style == 1) {
        // Output for Part 1
        printf("Total Requests: %d\n", cache->requests);
        printf("     Miss Rate: %.2f%%\n", ((float)cache->misses / (float)cache->requests) * 100);
        printf("------------------------------------------------------------\n");
    } else if (style == 2) {
        // Output for Part 2
        printf("Cache Layer: L%d\n", cache->layer);
        printf("----------------\n");
        printf("Configuration:\n");
        printf("    Size: %d bytes\n", cache->cache_size);
        printf("    Line Size: %d bytes\n", cache->line_size);
        printf("    Line Count: %d\n", cache->num_lines);
        printf("Performance Metrics:\n");
        printf("    Total Requests: %d\n", cache->requests);
        printf("    Hits: %d\n", cache->hits);
        printf("    Misses: %d\n", cache->misses);
        printf("    Hit Rate: %.2f%%\n", ((float)cache->hits / (float)cache->requests) * 100);
        printf("    Miss Rate: %.2f%%\n", ((float)cache->misses / (float)cache->requests) * 100);
        printf("    Read to Write Ratio: %d\n", cache->read_to_write);
        printf("    Write to Write Ratio: %d\n", cache->write_to_write);
    }
}

/**
 * @brief Converts an integer to binary
 * 
 * @param num 
 * @return char* 
 */
char* itob(int num) {
    size_t numBits = sizeof(int) * 8;
    char* binaryStr = (char*)malloc(numBits + 1);
    if (binaryStr == NULL) {
        fprintf(stderr, "Memory allocation failed.\n");
        return NULL;
    }

    binaryStr[numBits] = '\0';  // Null terminator

    for (size_t i = 0; i < numBits; ++i) {
        binaryStr[numBits - 1 - i] = (num & (1 << i)) ? '1' : '0';
    }

    return binaryStr;
}

/**
 * @brief Converts binary to an integer
 * 
 * @param binary 
 * @return int 
 */
int btoi(char* binary) {
    int value = 0;
    size_t len = strlen(binary); // Get the length of the binary string

    for (size_t i = 0; i < len; ++i) {
        value <<= 1;  // Shift the current value to the left by one bit
        if (binary[i] == '1') {
            value += 1;  // Add 1 if the current binary digit is 1
        } else if (binary[i] != '0') {
            // Handle invalid characters in the binary string
            fprintf(stderr, "Invalid character '%c' in binary string.\n", binary[i]);
            return 0; // Return 0 or an appropriate error value
        }
    }

    return value;
}
