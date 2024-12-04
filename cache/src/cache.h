/*
  ===========================================================================
  PROJECT: Direct-Mapped Write-Back Cache [Trace Driven Simulation]
  ===========================================================================
  NAME : Tyler Neal
  USER ID : tpneal
  DATE : 03/25/2024
  FILE NAME : cache.h
  PROGRAM PURPOSE:
    This header file declares the structures necessary to model a direct-mapped
    write-back cache. It provides declarations for cache blocks, sets, and the
    entire cache structure.

  PSEUDO :
    1. Process relevant simulation information such as line_size, cache_size,
  ect.
    2. Instantiate cache, as well as its set of lines.
    3. Parse stdin for address references.
    4. Decode address reference into request.
    5. Process request as a read or write operation
  ===========================================================================
*/

#ifndef CACHE_H
#define CACHE_H

#include <math.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

// Memory request times according to project specs
#define HIT_TIME_L1 1
#define HIT_TIME_L2 16
#define HIT_TIME_L3 64
#define MEM_ACCESS_TIME 100

// According to our implementation, these values wont exceed their defined max
#define INSTRUCTION_SIZE 32
#define MAX_TAG_SIZE 21
#define MAX_INDEX_SIZE 16
#define MAX_OFFSET_SIZE 5

// Used for debug printing
#define DEBUG false

typedef struct Line {
  int dirty;
  char tag[MAX_TAG_SIZE];
} Line;

typedef struct Cache {
  // Cache Details
  int cache_size;
  int line_size;
  int num_lines;
  int layer;
  Line *lines;

  // Feild Sizes
  int tag_size;
  int index_size;
  int offset_size;

  // Recorded Metrics
  int requests;
  int hits;
  int misses;
  int read_to_write;
  int write_to_write;
} Cache;

typedef struct Request {
  char ref_type;
  char access_type;

  unsigned int address; // Hex address formatted as int
  char tag[MAX_TAG_SIZE];
  char index[MAX_INDEX_SIZE];
  char offset[MAX_OFFSET_SIZE];
} Request;

// Function prototypes
Cache* constructCache(int cache_size, int line_size, int layer);
void destroyCache(Cache *cache);
Cache* setupCache(int layer, int cache_size, int line_size);

Request* createRequest();
void destroyRequest(Request *request);
void formatRequest(Request* request, Cache* cache, char* buffer);

void processRequest(Request *request, Cache *cache, bool *data_found);
void readData(Cache *cache, Request *request, bool *data_found);
void writeData(Cache *cache, Request *request, bool *data_found);

void printCacheStats(Cache *cache, int style);
char* itob(int num);
int btoi(char *binary);

#endif // CACHE_H
