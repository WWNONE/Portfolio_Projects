#ifndef SYMTAB_STACK_H_
#define SYMTAB_STACK_H_

#include "general.h"
#include "symtab.h"
#include "dlink.h"

#define SYMTABLE_SIZE 100

typedef DList SymtabStack;

EXTERN(SymtabStack, symtabStackInit, (void));
EXTERN(SymTable, beginScope, (SymtabStack stack));
EXTERN(SymTable, endScope, (SymtabStack stack));
EXTERN(SymTable, findSymtab, (SymtabStack stack, char* key));
EXTERN(SymTable, currentSymtab, (SymtabStack stack));
EXTERN(SymTable, lastSymtab, (SymtabStack stack));
#endif
