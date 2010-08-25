#include <stdio.h>
#include <string.h>
//readline
#include <sys/types.h>
#ifdef HAVE_STDLIB_H
#  include <stdlib.h>
#else 
extern void exit();
#endif
#ifdef READLINE_LIBRARY
#  include "readline.h"
#  include "history.h"
#else
#  include <readline/readline.h>
#  include <readline/history.h>
#endif
extern HIST_ENTRY **history_list ();
//lua
#include "lua.h"
#include "lualib.h"
#include "lauxlib.h"

/* the Lua interpreter */
lua_State* L;

int main ( int argc, char *argv[] )
{
  char *temp, *prompt;
  int done;
  temp = (char *)NULL;
  prompt = "                                                                         ";
  done = 0;

  char buff[256];
  int error;
  char c;
  
  int ret;
	/* initialize Lua */
	L = lua_open();
	/* load Lua base libraries */
	luaL_openlibs(L);
	/* run the script */
	luaL_dofile(L, "cliorc.lua");
	lua_getglobal(L, "init");
	lua_call(L,0,1);
	ret = (int)lua_tonumber(L, -1);
  lua_pop(L, 1);
  if (ret!=0){
    lua_close(L);
    return 1;
  }
	//lua_getglobal(L, "luarun");
	//lua_call(L,0,0);

  lua_getglobal(L, "call_cexec");
  lua_pushstring(L,"");
  error=lua_pcall(L,1,0,0);
  if (error) {
    fprintf(stderr, "%s", lua_tostring(L, -1));
    lua_pop(L, 1);  
  }
  
  /*while (fgets(buff, sizeof(buff), stdin) != NULL) {
      if(buff[0]==113 && buff[1]==10){break;} */
  while (!done){
/*      do{
        c=getchar();
        putchar(c);
      }while(c!='q');
      break;
*/
    //temp = readline (prompt);
    lua_getglobal(L, "getprompt");
    error=lua_pcall(L,0,1,0);
    if (error) {
      fprintf(stderr, "%s", lua_tostring(L, -1));
      lua_pop(L, 1);  
    }
   	prompt = lua_tostring(L, -1);
    lua_pop(L, 1);
    
    //buff="pepe"
    //strcpy(buff, rl_line_buffer);
    //rl_insert_text("pepe");
    temp = readline(prompt);
    if (!temp){
      ret=1;
      done = 1;
    }else if (strcmp (temp, "quit") == 0){
      ret = 0;
      done = 1;
    }else if (strcmp (temp, "list") == 0){
	    HIST_ENTRY **list;
	    register int i;
	    list = history_list ();
	    if (list){
	      for (i = 0; list[i]; i++) fprintf (stderr, "%d: %s\r\n", i, list[i]->line);
	    }
    }else if (*temp){
      add_history (temp);
      lua_getglobal(L, "call_cexec");
      lua_pushstring(L,temp);
      error=lua_pcall(L,1,1,0);
      if (error) {
        fprintf(stderr, "%s", lua_tostring(L, -1));
        lua_pop(L, 1);  
      }
     	ret = (int)lua_tonumber(L, -1);
      lua_pop(L, 1);
      if (ret!=0) done=1;
	  }
    free (temp);
  }
	lua_close(L);
  exit (ret);

	return ret;
}

