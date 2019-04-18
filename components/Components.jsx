import React from 'react'
import Parser from 'html-react-parser'

export function Autocomplete(props, state, autocomplete, onBlur, onChange, onDown) {
    var current = ''
    var length = 0
    var list = []
    for(var key in autocomplete.data) {
        if(length == autocomplete.position) {
            list.push(<li key={current = key}>{Parser(autocomplete.data[key])}</li>)
        } else {
            list.push(<li key={key} style={{display:'block'}}>{Parser(autocomplete.data[key])}</li>)
        }
        length++
    }
    return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   current={current}
                   data-props={JSON.stringify(props)}
                   length={length}
                   onBlur={onBlur}
                   onChange={onChange}
                   onKeyDown={onDown}
                   value={state}
                   type={'text'} />{list}
    </div>
}

export function Button(props, state, onClick) {
    return <a className={state && state.className ? state.className :  'btn btn-success'} onClick={onClick} style={{marginTop:'10px'}}>{props.label}</a>
}

export function Checkbox(props, state, onChange) {
    return <label style={{marginRight:'10px'}}>
        <input checked={'1' == state ? 'checked' : ''}
               onChange={onChange}
               style={{marginRight:'10px'}}
               type={'checkbox'}
               value={state} />{props.label}
    </label>
}

function diff(origin, state) {
    var newState = new Object();
    var newOrigin = new Object();
    for(var i = 0;i < state.length; i++) {
      if(null == newState[state[i]]) {
        newState[state[i]] = { rows: new Array(), origin: null }
      }
      newState[state[i]].rows.push(i)
    }
    for(var i = 0;i < origin.length; i++) {
      if(null == newOrigin[origin[i]]) {
        newOrigin[origin[i]] = { rows: new Array(), state: null }
      }
      newOrigin[origin[i] ].rows.push(i)
    }
    for(var i in newState) {
      if(1 == newState[i].rows.length && 'undefined' != typeof(newOrigin[i]) && 1 == newOrigin[i].rows.length) {
        state[newState[i].rows[0] ] = { text: state[ newState[i].rows[0] ], row: newOrigin[i].rows[0] };
        origin[newOrigin[i].rows[0] ] = { text: origin[ newOrigin[i].rows[0] ], row: newState[i].rows[0] }
      }
    }
    for(var i = 0; i < state.length - 1; i++ ) {
      if(state[i].text != null && state[i+1].text == null && state[i].row + 1 < origin.length && null == origin[state[i].row + 1 ].text && 
           state[i+1] == origin[state[i].row + 1 ]) {
        state[i+1] = { text: state[i+1], row: state[i].row + 1 }
        origin[state[i].row+1] = { text: origin[state[i].row+1], row: i + 1 }
      }
    }
    for(var i = state.length - 1; i > 0; i--) {
      if(null != state[i].text && null == state[i-1].text && state[i].row > 0 && null == origin[state[i].row - 1].text && 
           state[i-1] == origin[state[i].row - 1 ] ) {
        state[i-1] = { text: state[i-1], row: state[i].row - 1 }
        origin[state[i].row-1] = { text: origin[state[i].row-1], row: i - 1 }
      }
    }
    return { origin: origin, state: state }
}

export function Difference(origin, state) {
    origin = origin.replace(/\s+$/, '')
    var originTags = origin.match(/\s+/g)
    if (originTags == null) {
      originTags = ["\n"];
    } else {
      originTags.push("\n");
    }
    state = state.replace(/\s+$/, '')
    var stateTags = state.match(/\s+/g)
    if (stateTags == null) {
      stateTags = ["\n"];
    } else {
      stateTags.push("\n");
    }
    var out = diff('' == origin ? [] : origin.split(/\s+/), '' == state ? [] : state.split(/\s+/))
    var output = ''
    if (out.state.length == 0) {
        for (var i = 0; i < out.origin.length; i++) {
          output += '<del>' + escape(out.origin[i]) + originTags[i] + '</del>';
        }
    } else {
      if (out.state[0].text == null) {
        for (var n = 0; n < out.origin.length && out.origin[n].text == null; n++) {
          output += '<del>' + escape(out.origin[n]) + originTags[n] + '</del>';
        }
      }
      for ( var i = 0; i < out.state.length; i++ ) {
        if (out.state[i].text == null) {
          output += '<ins>' + escape(out.state[i]) + stateTags[i] + '</ins>';
        } else {
          var pre = '';
          for (var n = out.state[i].row + 1; n < out.origin.length && out.origin[n].text == null; n++) {
            pre += '<del>' + escape(out.origin[n]) + originTags[n] + '</del>';
          }
          output += ' ' + out.state[i].text + stateTags[i] + pre;
        }
      }
    }
    return Parser(output)
}

export function Download(props, state) {
    if(state) {
       return <a className={'list-group-item list-group-item-success'} download href={state} style={{marginTop:'10px'}}>{props.label}
                <span className={'glyphicon glyphicon-remove'} style={{float:'right'}}></span>
              </a>
    }
}

function escape(string) {
    string = string.replace(/&/g, '&amp;');
    string = string.replace(/</g, '&lt;');
    string = string.replace(/>/g, '&gt;');
    string = string.replace(/"/g, '&quot;');
    return string;
}

export function Icon(props, onClick) {
    return <button onClick={onClick} type={'button'}><span aria-hidden={'true'} className={props.className ? props.className : 'glyphicon glyphicon-edit'}></span></button>
}

export function Info(props, state, onClick) {
    if(state) {
        return <div className={'alert alert-warning alert-dismissible show'} role={'alert'} onClick={onClick} >
            <strong>{props.label}</strong>
            <button aria-label={'Close'} className={'close'} type={'button'}>
              <span aria-hidden={'true'}>&times;</span>
            </button>
          </div>
    }
}

export function Label(props) {
    return <label>{props.label}</label>
}

export function MultiSelect(autocomplete, props, data, onBlur, onChange, onClick, onKey, onRemove) {
    var values = {}
    var state = data ? data : []
    for(var value in state) {
        values[state[value]] = true
    }
    var container = []
    var options = []
    props.sum = 0
    if(autocomplete.id == props.id) {
        options.push(<li className={'list-group-item'}
                         onClick={onBlur}
                         key={props.id + '-cancel'}
                         style={props.sum == autocomplete.position ? {backgroundColor:'rgb(51, 122, 183)',color:'white'} : {}}
                         value={value}>{props.cancel}</li>)
        props.sum++
    }
    for(var value in props.data) {
        var key = value.replace('_', '')
        if(undefined != values[key]) {
            container.push(<li className={'list-group-item'}
                               data-props={JSON.stringify({id:props.id,value:key})}
                               onClick={onRemove}
                               key={props.id + '-' + value}>
                        {props.data[value]}
                        <span className={'glyphicon glyphicon-remove'} data-props={JSON.stringify({id:props.id,value:key})} style={{float:'right'}}></span>
                    </li>)
        } else if(autocomplete.id == props.id && null != props.data[value].toLowerCase().match(autocomplete.value.toLowerCase())) {
            if(props.sum == autocomplete.position && state) { var selected = parseInt(value.replace('_', '')) }
            options.push(<li className={'list-group-item'}
                             data-props={JSON.stringify({id:props.id,value:key})}
                             onClick={onClick}
                             key={props.id + '-' + value}
                             style={props.sum == autocomplete.position ? {backgroundColor:'rgb(51, 122, 183)',color:'white'} : {}}>{props.data[value]}</li>)
            props.sum++
        }
    }
    return <div key={'elements-' + props.id}><label>{props.label}</label>
            {container.length ? <ul className={'list-group'}>{container}</ul> : ''}
            <div className={'input-group'}>
                <input className={'form-control'}
                       data-props={JSON.stringify({id:props.id,state:state,sum:props.sum,value:selected})}
                       key={props.id + '-autocomplete'}
                       onClick={onChange}
                       onChange={onChange}
                       onKeyDown={onKey}
                       placeholder={props.placeholder}
                       type={'text'}
                       value={autocomplete.value && autocomplete.id == props.id ? autocomplete.value : ''} />
                {options.length ? <div className={'input-group-btn'}>
                    <ul className={'dropdown-menu dropdown-menu-right list-group'}
                        size={100}
                        style={{display:'block',left:'-100px',position:'absolute',zIndex:99,maxHeight:'300px',overflowY:'scroll'}}>
                        {options}
                    </ul>
                </div> : ''}
            </div>
        </div>
}

export function Number(props, state, onChange) {
    return <input className={'form-control'}
                   onChange={onChange}
                   style={props.style ? props.style : {}}
                   value={state}
                   title={props.title ? props.title : ''}
                   type={'number'} />
}

export function Paginator(props, state, onClick) {
    var pages = []
    for (var page = 1; page <= state.last; page++) {
        if(page == state.current) {
            pages.push(<li className={'page-item active'} key={page}>
                <a className={'page-link'} data-page={page} title={props.page + ' '  + page}>{page}</a></li>)
        } else if(state.current - 10 == page) {
            pages.push(<li key={'previous-page'}><a aria-label={'Previous'} className={'page-link'} data-page={state.current - 1} onClick={onClick} title={props.previous}>
                        <span aria-hidden={'true'} data-page={state.current - 1}>&laquo;</span></a></li>)
        } else if(state.current + 10 == page) {
            pages.push(<li key={'next-page'}><a aria-label={'Next'} className={'page-link'} data-page={state.current + 1} onClick={onClick} title={props.next}>
                        <span aria-hidden={'true'} data-page={state.current + 1}>&raquo;</span></a></li>)
        } else if(page < state.current - 10 || page > state.current + 10) {
        } else {
            pages.push(<li className={'page-item'} key={page}><a className={'page-link'} data-page={page} onClick={onClick} title={props.page + ' ' + page}>{page}</a></li>)
        }
    }
    pages.push(<li className={'page-item'} key={'sum'}><a className={'page-link'} title={props.sum + ' ' + state.sum}>{props.sum + ' ' + state.sum}
            <i className={'fa fa-fw fa-database'}></i>
    </a></li>)
    return <ul className={'pagination'}>{pages}</ul>
}

export function Password(props, state, onChange) {
    return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   onChange={onChange}
                   value={state}
                   type={'password'} />
    </div>
}

export function ProgressBar(props, state) {
    return <div className={'progress'} key={props.id + '-progress'} style={{marginTop:'10px'}}>
            <div className={'progress-bar'} style={state ? {width:state + '%'} : {}}></div>
          </div>
}

export function RadioList(props, state, onChange) {
    var container = []
    for(var key in props.data) {
        var checked = false
        if(state == key) {
            checked = 'checked'
        }
        container.push(<label key={props.id + '-' + key}>
                <input checked={checked}
                       className={'form-control'}
                       name={props.id}
                       onChange={onChange}
                       value={key}
                       type={'radio'} />{props.data[key]}
                </label>)
    }
    return <div className={'form-group'} key={props.id}><label>{props.label}</label>{container}</div>
}

export function SelectBox(props, state, onChange) {
    var options = []
    options.push(props.placeholder ? <option key={props.id + '-prompt'}>{props.placeholder}</option> : '')
    for(var key in props.data) {
        if(props.data[key] == state || key == state) {
            options.push(<option selected key={key} value={key}>{props.data[key]}</option>)
        } else {
            options.push(<option key={key} value={key}>{props.data[key]}</option>)
        }
    }
    return <div className={'form-group'}><label>{props.label}</label><select className={'form-control'} key={props.id} onChange={onChange}>{options}</select></div>
}

export function Sort(props, state, onClick) {
    return <th className={'fa-hover sort'} key={props.id + '-sort'} onClick={onClick}>
        <a className={'fa-hover'} href={'javascript:;'}>
            {props.label}<i aria-hidden={'true'} className={state ? 'fa fa-sort' + state : 'fa fa-sort'}></i>
        </a>
    </th>
}

export function Text(props, state, onChange) {
    return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <input className={'form-control'}
                   onChange={onChange}
                   value={state}
                   type={'text'} />
    </div>
}

export function TextArea(props, state, onChange) {
    return <div className={'form-group'}>
            <label htmlFor={props.label}>{props.label}</label>
            <textarea className={'form-control'}
                   onChange={onChange}
                   value={state}
                   type={'textarea'} >{state}</textarea>
    </div>
}

export function Warning(state) {
    if(undefined != state && state.length > 0) {
        return <div className={'warning'}>{state}</div>
    }
}