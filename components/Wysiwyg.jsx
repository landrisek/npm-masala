import React from 'react'
import {convertToRaw, EditorState} from 'draft-js'
import {Editor} from 'react-draft-wysiwyg'
import draftToHtml from 'draftjs-to-html'
import {stateFromHTML} from 'draft-js-import-html'
import 'react-draft-wysiwyg/dist/react-draft-wysiwyg.css'

export class Wysiwyg extends React.Component {
    onChangeWysiwyg(props, state) {
        let wysiwyg = this.state.Wysiwyg
        wysiwyg[props.id] = state
        let changed = this.OnChangeWysiwyg(props, draftToHtml(convertToRaw(state.getCurrentContent())))
        changed.Wysiwyg = wysiwyg
        this.setState(changed)
    }
    OnChangeWysiwyg(props, state) {
        return {[props.id]: state.substr(3, state.length - 9)}
    }
    Wysiwyg(props, state) {
        let self = this
        if(undefined == this.state.Wysiwyg[props.id]) {
            this.state.Wysiwyg[props.id] = EditorState.createWithContent(stateFromHTML(state))
        }
        return <><label>{props.label}</label><Editor editorClassName={'form-control'}
                                                     editorState={this.state.Wysiwyg[props.id]}
                                                     onEditorStateChange={function (event) { self.onChangeWysiwygs(props, event); } }
                                                     toolbarClassName={'toolbarClassName'}
                                                     wrapperClassName={'wrapperClassName'} /></>
    }
}