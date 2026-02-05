import React, { useState } from 'react';
import { Book, Plus, Calendar, Settings, Layout, Mic, Search, Check, X, Backpack, Sparkles, Loader2, Home } from 'lucide-react';
import { SubjectRegister, AppView } from '../types';

interface SidebarProps {
  registers: SubjectRegister[];
  currentView: AppView;
  onChangeView: (view: AppView) => void;
  onSelectSubject: (subject: string) => void;
  selectedSubject: string | null;
  onSearch: (query: string) => void;
  onSmartSearch: () => void;
  isSearching: boolean;
  searchQuery: string;
  onCreateRegister: (name: string) => void;
  isMobileOpen: boolean;
  onMobileClose: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ 
  registers, 
  currentView, 
  onChangeView, 
  onSelectSubject,
  selectedSubject,
  onSearch,
  onSmartSearch,
  isSearching,
  searchQuery,
  onCreateRegister,
  isMobileOpen,
  onMobileClose
}) => {
  const [isCreating, setIsCreating] = useState(false);
  const [newRegisterName, setNewRegisterName] = useState('');

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newRegisterName.trim()) {
        onCreateRegister(newRegisterName.trim());
        setNewRegisterName('');
        setIsCreating(false);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === 'Enter') {
          onSmartSearch();
      }
  };

  // Content for the Sidebar/Drawer
  const SidebarContent = () => (
    <>
      {/* Brand Header (Desktop Only) */}
      <div className="hidden md:block p-6 pt-8 pb-4">
        <div className="flex items-center gap-3 text-stone-800">
          <div className="bg-orange-100 p-2.5 rounded-xl border border-orange-200">
            <Backpack className="text-orange-600 w-6 h-6" />
          </div>
          <h1 className="font-hand font-bold text-2xl tracking-wide">ScholarAI</h1>
        </div>
      </div>

      {/* Global Actions */}
      <div className="px-5 space-y-4 mb-4 mt-6 md:mt-0">
        <div className="relative group">
            {isSearching ? (
                <Loader2 className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-orange-500 animate-spin" />
            ) : (
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 group-focus-within:text-orange-500 transition-colors" />
            )}
            
            <input 
                type="text" 
                placeholder="Search notes... (Press Enter for AI)" 
                value={searchQuery}
                onChange={(e) => onSearch(e.target.value)}
                onKeyDown={handleKeyDown}
                className="w-full bg-white border border-stone-200 rounded-xl py-2.5 pl-10 pr-9 text-sm text-stone-700 focus:outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-100 transition-all shadow-sm"
            />
            
            {searchQuery && !isSearching && (
                <button 
                    onClick={onSmartSearch}
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-stone-300 hover:text-orange-500 transition-colors"
                    title="AI Smart Search"
                >
                    <Sparkles className="w-4 h-4" />
                </button>
            )}
        </div>

        <button 
          onClick={() => onChangeView(AppView.RECORDER)}
          className="hidden md:flex w-full bg-stone-800 hover:bg-stone-900 text-white py-3 px-4 rounded-xl items-center justify-center gap-2 font-medium transition-all shadow-lg shadow-stone-300 active:scale-95"
        >
          <Plus className="w-5 h-5" />
          <span className="font-hand text-lg">New Entry</span>
        </button>
      </div>

      {/* Navigation "Bookshelf" */}
      <nav className="flex-1 overflow-y-auto px-4 space-y-8 pb-6">
        
        {/* Desk Items (Desktop Only - Mobile has Bottom Nav) */}
        <div className="hidden md:block">
          <p className="text-xs font-bold uppercase text-stone-400 mb-3 px-2 tracking-wider">My Desk</p>
          <ul className="space-y-1">
            <li>
              <button 
                onClick={() => {
                   onChangeView(AppView.DASHBOARD);
                   onSelectSubject('');
                }}
                className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all ${currentView === AppView.DASHBOARD && !selectedSubject ? 'bg-white shadow-sm border border-stone-100 text-orange-600' : 'hover:bg-stone-100 text-stone-600'}`}
              >
                <Layout className="w-4 h-4" />
                <span className="font-medium">All Notes</span>
              </button>
            </li>
            <li>
               <button 
                onClick={() => onChangeView(AppView.STUDY_MODE)}
                className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all ${currentView === AppView.STUDY_MODE ? 'bg-white shadow-sm border border-stone-100 text-orange-600' : 'hover:bg-stone-100 text-stone-600'}`}
              >
                <Calendar className="w-4 h-4" />
                <span className="font-medium">Study Planner</span>
              </button>
            </li>
          </ul>
        </div>

        {/* Registers Shelf */}
        <div>
          <div className="flex items-center justify-between px-2 mb-3">
            <p className="text-xs font-bold uppercase text-stone-400 tracking-wider">Registers</p>
            <button 
                onClick={() => setIsCreating(true)} 
                className="p-1 hover:bg-stone-200 rounded transition-colors"
                title="Create new register"
            >
                <Plus className="w-3 h-3 text-stone-500" />
            </button>
          </div>
          
          <ul className="space-y-2">
            {isCreating && (
                <li className="px-2 py-1 animate-slide-up">
                    <form onSubmit={handleCreateSubmit} className="flex items-center gap-2 bg-white p-2 rounded-lg border border-orange-200 shadow-sm">
                        <input 
                            autoFocus
                            type="text" 
                            value={newRegisterName}
                            onChange={(e) => setNewRegisterName(e.target.value)}
                            className="w-full bg-transparent text-sm text-stone-800 focus:outline-none font-hand"
                            placeholder="Subject Name..."
                        />
                        <button type="submit" className="text-green-600 hover:text-green-700"><Check className="w-4 h-4" /></button>
                        <button type="button" onClick={() => setIsCreating(false)} className="text-red-400 hover:text-red-500"><X className="w-4 h-4" /></button>
                    </form>
                </li>
            )}

            <li>
              <button 
                  onClick={() => onSelectSubject('')}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all ${!selectedSubject ? 'bg-white shadow-sm ring-1 ring-black/5' : 'hover:bg-stone-100'}`}
                >
                  <div className="w-1.5 h-6 rounded-sm bg-stone-300 shadow-sm"></div>
                  <span className={`truncate flex-1 text-left font-hand text-lg ${!selectedSubject ? 'text-stone-900 font-bold' : 'text-stone-600'}`}>
                      All Subjects
                  </span>
              </button>
            </li>

            {registers.map((reg) => (
              <li key={reg.name}>
                <button 
                  onClick={() => onSelectSubject(reg.name)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all group relative overflow-hidden ${selectedSubject === reg.name ? 'shadow-sm ring-1 ring-black/5' : 'hover:bg-stone-100'}`}
                >
                    {/* Active State Background with slight tint based on register color */}
                    {selectedSubject === reg.name && (
                        <div className={`absolute inset-0 opacity-10 ${reg.color.split(' ')[0]}`}></div>
                    )}

                    {/* Spine Color Indicator */}
                    <div className={`w-1.5 h-6 rounded-sm ${reg.color.split(' ')[0].replace('bg-', 'bg-')} shadow-sm`}></div>
                    
                    <span className={`truncate flex-1 text-left font-hand text-lg ${selectedSubject === reg.name ? 'text-stone-900 font-bold' : 'text-stone-600'}`}>
                        {reg.name}
                    </span>
                    
                    {reg.noteIds.length > 0 && (
                        <span className="text-[10px] font-bold text-stone-400 bg-stone-100 px-1.5 py-0.5 rounded-full">
                            {reg.noteIds.length}
                        </span>
                    )}
                </button>
              </li>
            ))}
            
            {!isCreating && registers.length === 0 && (
                <li className="px-4 py-8 text-center border-2 border-dashed border-stone-200 rounded-xl">
                    <p className="text-xs text-stone-400 font-hand text-lg">Shelf empty</p>
                </li>
            )}
          </ul>
        </div>
      </nav>

      <div className="p-4 border-t border-stone-200/60 hidden md:block">
        <button className="flex items-center gap-3 text-sm text-stone-500 hover:text-orange-600 transition-colors px-3 py-2">
          <Settings className="w-4 h-4" />
          Settings
        </button>
      </div>
    </>
  );

  return (
    <>
        {/* Desktop Sidebar (Fixed) */}
        <div className="hidden md:flex w-72 bg-stone-50 text-stone-700 flex-col h-full border-r border-stone-200/60 flex-shrink-0 shadow-[4px_0_24px_rgba(0,0,0,0.02)] z-30">
            <SidebarContent />
        </div>

        {/* Mobile Side Drawer (Overlay) */}
        {isMobileOpen && (
            <div className="fixed inset-0 z-50 md:hidden flex">
                <div className="absolute inset-0 bg-black/30 backdrop-blur-sm" onClick={onMobileClose}></div>
                <div className="w-64 bg-stone-50 h-full shadow-2xl relative flex flex-col animate-slide-right">
                    <button onClick={onMobileClose} className="absolute top-4 right-4 p-2 text-stone-400 hover:text-stone-600">
                        <X className="w-5 h-5" />
                    </button>
                    <div className="p-4 pt-6 pb-0">
                         <h2 className="font-hand font-bold text-xl text-stone-800">My Shelf</h2>
                    </div>
                    <SidebarContent />
                </div>
            </div>
        )}

        {/* Mobile Bottom Navigation (Bottom Panel) */}
        <div className="md:hidden fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-lg border-t border-stone-200 h-16 flex items-center justify-around z-40 pb-safe">
            <button 
                onClick={() => onChangeView(AppView.DASHBOARD)}
                className={`flex flex-col items-center gap-1 p-2 ${currentView === AppView.DASHBOARD ? 'text-orange-600' : 'text-stone-400'}`}
            >
                <Home className="w-5 h-5" />
                <span className="text-[10px] font-bold uppercase">Desk</span>
            </button>
            
            <button 
                onClick={() => onChangeView(AppView.RECORDER)}
                className="relative -top-5 bg-stone-800 text-white p-4 rounded-full shadow-lg shadow-stone-300 active:scale-95 transition-transform"
            >
                <Plus className="w-6 h-6" />
            </button>
            
            <button 
                onClick={() => onChangeView(AppView.STUDY_MODE)}
                className={`flex flex-col items-center gap-1 p-2 ${currentView === AppView.STUDY_MODE ? 'text-orange-600' : 'text-stone-400'}`}
            >
                <Calendar className="w-5 h-5" />
                <span className="text-[10px] font-bold uppercase">Study</span>
            </button>
        </div>
    </>
  );
};

export default Sidebar;