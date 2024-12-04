/*******************************************************/
/*                     Cminus Parser                   */
/*                                                     */
/*******************************************************/

/*********************DEFINITIONS***********************/
%{
#include <stdlib.h>
#include <strings.h>
#include <string.h>
#include <stdbool.h>
#include <util/general.h>
#include <util/symtab.h>
#include <util/symtab_stack.h>
#include <util/dlink.h>
#include <util/string_utils.h>
#include <codegen/symfields.h>
#include <codegen/types.h>
#include <codegen/reg.h>

#define SYMTABLE_SIZE 100

/***********************ADDED DECLARATIONS*************************/

// Functions
char* newLabel();
char* getPrevLabel(int i);
bool isArray(int symIndex);
static int freeVarDecNode(DLinkNode* node);

// Variables
int labelCount;

/*********************EXTERNAL DECLARATIONS***********************/

EXTERN(void, Cminus_error, (char*));

EXTERN(int, Cminus_lex, (void));

SymTable symtab; // Symbol table for managing variable offsets

static DList instList;
static DList dataList;

char *fileName;	

static int functionOffset;
int globalOffset = 0;
static char* functionName;

extern union YYSTYPE yylval;

extern int Cminus_lineno;

%}
%code requires {
	#include <stdio.h>
	#include "../codegen/codegen.h"
	// Placing this here prevents unrecognized struct errors with varDecl
}

%union {
	int		num;
	char*	name;
	char* 	label;
	int     symIndex;
	DList 	idList;
	int 	offset;
	varDecl* decl; 		// Wrapper used to pass variable regIndex and arrSize up to declaration function.
}

%name-prefix="Cminus_"
%defines

%start Program

%token AND
%token ELSE
%token EXIT
%token FLOAT
%token FOR
%token IF 		
%token INTEGER 
%token NOT 		
%token OR 		
%token READ
%token WHILE
%token WRITE
%token LBRACE
%token RBRACE
%token LE
%token LT
%token GE
%token GT
%token EQ
%token NE
%token ASSIGN
%token COMMA
%token SEMICOLON
%token LBRACKET
%token RBRACKET
%token LPAREN
%token RPAREN
%token PLUS
%token TIMES
%token IDENTIFIER
%token DIVIDE
%token RETURN
%token STRING
%token INTCON
%token FLOATCON
%token MINUS

%left OR
%left AND
%left NOT
%left LT LE GT GE NE EQ
%left PLUS MINUS
%left TIMES DIVDE

%type <idList> IdentifierList
%type <decl> VarDecl
%type <label> Test Jump WhileToken WhileExpr
%type <symIndex> Expr SimpleExpr AddExpr
%type <symIndex> MulExpr Factor Variable StringConstant Constant FunctionDecl ProcedureHead
%type <offset> DeclList
%type <name> IDENTIFIER STRING FLOATCON INTCON 
 
/**'
* IMPLEMENTATION GOALS:
*   1. Conditional Control (If and While Loop Logic)					[x]
*   2. If-Then-Else Branching (Ability to skip code)					[x]
*   3. While Looping and Control (Ability to loop or exit loops)		[x]
*   4. Array Storage (Reserve space for an array of integers)			[x]
*   5. Array Accessing (Generate addresses for array references.)		[x]
**/

/***********************PRODUCTIONS****************************/
%%
Program: 			Procedures 
					{
						emitDataPrologue(dataList);
						emitInstructions(instList);
					}
	  				| DeclList Procedures
					{
						globalOffset = $1;
						emitDataPrologue(dataList);
						emitInstructions(instList);
					}
;

Procedures:			ProcedureDecl Procedures
	   				|
;

ProcedureDecl: 		ProcedureHead ProcedureBody
               		{
						emitExit(instList);
               		}
;

ProcedureHead: 		FunctionDecl DeclList 
					{
						emitProcedurePrologue(instList, symtab, $1, $2);
						functionOffset = $2;
						$$ = $1;
					}
	      			| FunctionDecl
					{
						emitProcedurePrologue(instList, symtab, $1, 0);
						functionOffset = 0;
						$$ = $1;
					}
;

FunctionDecl: 		Type IDENTIFIER LPAREN RPAREN LBRACE 
					{
						$$ = SymIndex(symtab, $2);
					}
;

ProcedureBody: 		StatementList RBRACE
;
	
DeclList: 			Type IdentifierList SEMICOLON 
					{
						AddIdStructPtr data = (AddIdStructPtr)malloc(sizeof(AddIdStruct));

						data->offset = 0;
						data->offsetDirection = 1;
						data->symtab = symtab;

						dlinkApply1($2,(DLinkApply1Func)addIdToSymtab,(Generic)data);

						$$ = data->offset;

						dlinkFreeNodes($2);
						free(data);
					}		
					| DeclList Type IdentifierList SEMICOLON
					{
						AddIdStructPtr data = (AddIdStructPtr)malloc(sizeof(AddIdStruct));

						data->offset = $1;
						data->offsetDirection = 1;
						data->symtab = symtab;

						dlinkApply1($3,(DLinkApply1Func)addIdToSymtab,(Generic)data);

						$$ = data->offset;

						dlinkFreeNodes($3);
						free(data);
					}
;

IdentifierList: 	VarDecl  
					{
						$$ = dlinkListAlloc(NULL);
						dlinkAppend($$,dlinkNodeAlloc((Generic)$1));
					}
									
					| IdentifierList COMMA VarDecl
					{
						dlinkAppend($1,dlinkNodeAlloc((Generic)$3));
						$$ = $1;
					}
;

VarDecl: 			IDENTIFIER
					{ 
						// We wrap the data in order to transport it to addIdToSymtab function
						varDecl* vd = (varDecl*)malloc(sizeof(varDecl));
						vd->symIndex = SymIndex(symtab,$1);
						vd->arraySize = 0;	// 0 is the base-case, indicating non-array variable
						$$ = vd;
					}
					| IDENTIFIER LBRACKET INTCON RBRACKET
					{
						// We wrap the data in order to transport it to addIdToSymtab function
						varDecl* vd = (varDecl*)malloc(sizeof(varDecl));
						vd->symIndex = SymIndex(symtab,$1);
						vd->arraySize = atoi($3);
						$$ = vd;
					}
;

Type: 				INTEGER 
                	| FLOAT   
;

Statement: 			Assignment
                	| IfStatement
					| WhileStatement
                	| IOStatement 
					| ReturnStatement
					| ExitStatement	
					| CompoundStatement
;

Assignment: 		Variable ASSIGN Expr SEMICOLON
					{
						emitAssignment(instList, symtab, $1, $3);
					}
;

IfStatement: 		IF Test CompoundStatement Jump ELSE CompoundStatement
					{
						// Insert L2 label to skip else
						emitLabel(instList, $4);
						free($4);
					}
					| IF Test CompoundStatement
					{	
						// Emit L1 label to skip if
						emitLabel(instList, $2);
						free($2);
					}
;	

Jump:				// Blank rule allows jump and label insertion before else body
					{
						// Insert unconditional jump to L2 (if case)
						char* skipElse = newLabel();	
						emitUnconditionalJump(instList, skipElse, symtab);

						// Insert L1 label, used to skip if and begin else (else case)
						char* skipIf = getPrevLabel(1); // Couldn't think of a better way to pass labels without significantly modifying grammar rules
						emitLabel(instList, skipIf);
						free(skipIf);

						$$ = skipElse;
					}
;

Test:				LPAREN Expr RPAREN
					{
						// Insert branch logic
						char* skipIf = newLabel();
						emitConditionalJump(instList, skipIf, symtab, $2);
						$$ = skipIf;
					}
;

WhileStatement:		WhileToken WhileExpr Statement
					{	
						emitUnconditionalJump(instList, $1, symtab); // Loop back at end of loop body
						emitLabel(instList, $2);	// Label used for breaking loop

						free($1);
						free($2);
					}
;

WhileExpr: 			LPAREN Expr RPAREN
					{
						// Insert conditional logic, to evaluate loop status
						char* breakLoop = newLabel();
						emitConditionalJump(instList, breakLoop, symtab, $2);
						$$ = breakLoop;
					}
;

WhileToken: 		WHILE
					{
						// Insert label used to repeat loop
						char* loopStart = newLabel();
						emitLabel(instList, loopStart);
						$$ = loopStart;
					}
;

IOStatement:		READ LPAREN Variable RPAREN SEMICOLON
					{
						emitReadVariable(instList, symtab, $3);
					}
                	| WRITE LPAREN Expr RPAREN SEMICOLON
					{
						emitWriteExpression(instList, symtab, $3, SYSCALL_PRINT_INTEGER);
					}
                	| WRITE LPAREN StringConstant RPAREN SEMICOLON         
					{
						emitWriteExpression(instList, symtab, $3, SYSCALL_PRINT_STRING);
					}
;

ReturnStatement:	RETURN Expr SEMICOLON
;

ExitStatement:		EXIT SEMICOLON
					{
						emitExit(instList);
					}
;

CompoundStatement:	LBRACE StatementList RBRACE
;

StatementList:		Statement
					| StatementList Statement
;

Expr:				SimpleExpr
					{
						$$ = $1; 
					}
                	| Expr OR SimpleExpr 
					{
						$$ = emitOrExpression(instList, symtab, $1, $3);
					}
					| Expr AND SimpleExpr 
					{
						$$ = emitAndExpression(instList, symtab, $1, $3);
					}
					| NOT SimpleExpr 
					{
						$$ = emitNotExpression(instList, symtab, $2);
					}
;

SimpleExpr:			AddExpr
					{
						$$ = $1; 
					}
					| SimpleExpr EQ AddExpr
					{
						$$ = emitEqualExpression(instList, symtab, $1, $3);
					}
					| SimpleExpr NE AddExpr
					{
						$$ = emitNotEqualExpression(instList, symtab, $1, $3);
					}
					| SimpleExpr LE AddExpr
					{
						$$ = emitLessEqualExpression(instList, symtab, $1, $3);
					}
					| SimpleExpr LT AddExpr
					{
						$$ = emitLessThanExpression(instList, symtab, $1, $3);
					}
					| SimpleExpr GE AddExpr
					{
						$$ = emitGreaterEqualExpression(instList, symtab, $1, $3);
					}
					| SimpleExpr GT AddExpr
					{
						$$ = emitGreaterThanExpression(instList, symtab, $1, $3);
					}
;

AddExpr:			MulExpr            
					{
						$$ = $1; 
					}
					|  AddExpr PLUS MulExpr
					{
						$$ = emitAddExpression(instList, symtab, $1, $3);
					}
					|  AddExpr MINUS MulExpr
					{
						$$ = emitSubtractExpression(instList, symtab, $1, $3);
					}
;

MulExpr:			Factor
					{
						$$ = $1; 
					}
					|  MulExpr TIMES Factor
					{
						$$ = emitMultiplyExpression(instList, symtab, $1, $3);
					}
					|  MulExpr DIVIDE Factor
					{
						$$ = emitDivideExpression(instList, symtab, $1, $3);
					}		
;
		
Factor: 			Variable
					{ 
						$$ = emitLoadVariable(instList, symtab, $1);
					}
					| Constant
					{ 
						$$ = $1;
					}
					| IDENTIFIER LPAREN RPAREN
					{
						$$ = SYM_INVALID_INDEX; // Did not seem necessary to implement as there are no function calls.
					}
					| LPAREN Expr RPAREN
					{
						$$ = $2;
					}
;  

Variable:			IDENTIFIER
					{
						int symIndex = SymQueryIndex(symtab, $1);
						if (isArray(symIndex)) {
							exit(-1);
						}
						$$ = emitComputeVariableAddress(instList, symtab, symIndex); // Returns register symtab index holding address
					}
					| IDENTIFIER LBRACKET Expr RBRACKET
					{
						int symIndex = SymQueryIndex(symtab, $1);
						if (!isArray(symIndex)) {
							exit(-1);
						}

						$$ = emitComputeVariableArrayAddress(instList, symtab, symIndex, $3); // Returns register symtab index holding address
					}
;			       

StringConstant:		STRING
					{ 
						int symIndex = SymIndex(symtab, $1);
						$$ = emitLoadStringConstantAddress(instList, dataList, symtab, symIndex); 
					}
;

Constant:			INTCON
					{ 
						int symIndex = SymIndex(symtab, $1);
						$$ = emitLoadIntegerConstant(instList, symtab, symIndex); 
					}
;
%%

/******************** ADDED ROUTINES *********************************/

/**
 * Desc: Generates and returns a new label based on the global label counter
 * @returns {char*} - (see above)
 */
char* newLabel() {
    char buffer[20];
    snprintf(buffer, sizeof(buffer), "L%d", labelCount++);
    return strdup(buffer);
}

/**
 * Desc: Generates and returns label generated (i + 1 iterations ago) *probably 
 * not the most fluid way of doing things, but works for a single threaded execution.
 * @param {int} i - (see above)
 * @returns {char*} - (see above)
 */
char* getPrevLabel(int i) {
    char buffer[20];
    snprintf(buffer, sizeof(buffer), "L%d", (labelCount - 1 - i));
    return strdup(buffer);
}

/**
 * Desc: Return true if variable is an array, and false otherwise
 * @param {int} symIndex - index in symbol table containing variable information
 * @returns {bool} (see above)
 */
bool isArray(int symIndex) {
	int arraySize = SymGetFieldByIndex(symtab, symIndex, SYMTAB_ARRAY_SIZE_FIELD);
	if (arraySize <= 0) {
		return false;
	}
	return true;
}

/**
 * Desc: Free a linked list node containing a varDecl struct pointer
 * @param {DLinkNode} node - node containing varDecl struct
 * @returns {int} return 0 on sucess
 */
static int freeVarDecNode(DLinkNode* node) {
    varDecl* vd = (varDecl*)dlinkNodeAtom(node);
    free(vd);  // Free the varDec struct
    dlinkFreeNode(node);  // Free the node itself
	return 0;
}

/********************C ROUTINES *********************************/

void Cminus_error(char *s) {
  fprintf(stderr, "%s: line %d: %s\n", fileName, Cminus_lineno, s);
}

int Cminus_wrap() {
	return 1;
}

static void initSymTable() {

	symtab = SymInit(SYMTABLE_SIZE); 

	SymInitField(symtab, SYMTAB_OFFSET_FIELD, (Generic)-1, NULL);
	SymInitField(symtab, SYMTAB_REGISTER_INDEX_FIELD, (Generic)-1, NULL);
	SymInitField(symtab, SYMTAB_ARRAY_SIZE_FIELD, (Generic)-1, NULL); // Used for determining if var is array
}

static void deleteSymTable() {
    SymKillField(symtab, SYMTAB_REGISTER_INDEX_FIELD);
    SymKillField(symtab, SYMTAB_OFFSET_FIELD);
	SymKillField(symtab, SYMTAB_ARRAY_SIZE_FIELD);
    SymKill(symtab);

}

static void initialize(char* inputFileName) {

	stdin = freopen(inputFileName, "r", stdin);
	if (stdin == NULL) {
		fprintf(stderr, "Error: Could not open file %s\n", inputFileName);
		exit(-1);
	}

	char* dotChar = rindex(inputFileName, '.');
	int endIndex = strlen(inputFileName) - strlen(dotChar);
	char *outputFileName = nssave(2, substr(inputFileName, 0, endIndex), ".s");
	stdout = freopen(outputFileName, "w", stdout);
	if (stdout == NULL) {
		fprintf(stderr, "Error: Could not open file %s\n", outputFileName);
		exit(-1);
	} 

	initSymTable();	// Initialize the symbol table
	
	initRegisters(); // Initialize registers for usage
	
	instList = dlinkListAlloc(NULL);	// ???
	dataList = dlinkListAlloc(NULL);	// ???

}

static void finalize() {

    fclose(stdin);
    /*fclose(stdout);*/
    
    deleteSymTable();
 
    cleanupRegisters();
    
    dlinkFreeNodesAndAtoms(instList);
    dlinkFreeNodesAndAtoms(dataList);

}

int main(int argc, char** argv) {	
	fileName = argv[1];
	initialize(fileName);
	
        Cminus_parse();
  
  	finalize();
  
  	return 0;
}

/******************END OF C ROUTINES**********************/
