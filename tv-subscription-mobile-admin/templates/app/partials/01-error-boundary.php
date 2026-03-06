<?php if (!defined('ABSPATH')) { exit; } ?>
    /* ---------------------------------------------
        ERROR BOUNDARY
    --------------------------------------------- */
    class ErrorBoundary extends Component {
      constructor(p) { super(p); this.state = { err:null, info:null }; }
      static getDerivedStateFromError(e) { return { err:e }; }
      componentDidCatch(e, info) { console.error('[XOFLIX Admin Crash]', e, info); }
      render() {
        if (this.state.err) return el('div', { style:{display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',height:'100%',padding:40,textAlign:'center',background:'var(--bg)'} },
          el('div', { style:{width:64,height:64,borderRadius:'50%',background:'rgba(239,68,68,.12)',color:'var(--red)',display:'flex',alignItems:'center',justifyContent:'center',fontSize:28,fontWeight:900,marginBottom:16} }, '?'),
          el('h2', { style:{fontSize:20,fontWeight:800,marginBottom:8} }, 'Something went wrong'),
          el('p',  { style:{color:'var(--muted)',fontSize:13,marginBottom:8,maxWidth:300,lineHeight:1.6} }, String(this.state.err?.message||this.state.err||'Unknown error')),
          el('p',  { style:{color:'var(--muted)',fontSize:11,marginBottom:24,maxWidth:300,fontFamily:'monospace',wordBreak:'break-all'} },
            this.state.err?.stack?.split('\n')[1]?.trim()||''
          ),
          el('button', { className:'btn btn-primary press', onClick:()=>{ this.setState({err:null,info:null}); } }, 'Try Again'),
          el('button', { className:'btn btn-ghost press', style:{marginTop:10}, onClick:()=>location.reload() }, 'Reload App')
        );
        return this.props.children;
      }
    }