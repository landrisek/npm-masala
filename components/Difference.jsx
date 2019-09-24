import Parser from 'html-react-parser'
import React from 'react'

export class Difference extends React.Component {
    constructor(props) {
        super(props)
    }

    Difference(origin, state) {
        origin = origin.replace(/\s+$/, '');
        let originTags = origin.match(/\s+/g);
        if (originTags == null) {
            originTags = ["\n"];
        } else {
            originTags.push("\n");
        }
        state = state.replace(/\s+$/, '');
        let stateTags = state.match(/\s+/g);
        if (stateTags == null) {
            stateTags = ["\n"];
        } else {
            stateTags.push("\n");
        }
        let out = diff('' == origin ? [] : origin.split(/\s+/), '' == state ? [] : state.split(/\s+/));
        let output = '';
        if (out.state.length == 0) {
            for (let i = 0; i < out.origin.length; i++) {
                output += '<del>' + escape(out.origin[i]) + originTags[i] + '</del>';
            }
        } else {
            if (out.state[0].text == null) {
                for (let n = 0; n < out.origin.length && out.origin[n].text == null; n++) {
                    output += '<del>' + escape(out.origin[n]) + originTags[n] + '</del>';
                }
            }
            for (let i = 0; i < out.state.length; i++) {
                if (out.state[i].text == null) {
                    output += '<ins>' + escape(out.state[i]) + stateTags[i] + '</ins>';
                } else {
                    let pre = '';
                    for (let n = out.state[i].row + 1; n < out.origin.length && out.origin[n].text == null; n++) {
                        pre += '<del>' + escape(out.origin[n]) + originTags[n] + '</del>';
                    }
                    output += ' ' + out.state[i].text + stateTags[i] + pre;
                }
            }
        }
        return Parser(output)
    }
}

function diff(origin, state) {
    let newState = {};
    let newOrigin = {};
    for (let i = 0; i < state.length; i++) {
        if (null == newState[state[i]]) {
            newState[state[i]] = {rows: [], origin: null}
        }
        newState[state[i]].rows.push(i)
    }
    for (let i = 0; i < origin.length; i++) {
        if (null == newOrigin[origin[i]]) {
            newOrigin[origin[i]] = {rows: [], state: null}
        }
        newOrigin[origin[i]].rows.push(i)
    }
    for (let i in newState) {
        if (1 == newState[i].rows.length && 'undefined' != typeof (newOrigin[i]) && 1 == newOrigin[i].rows.length) {
            state[newState[i].rows[0]] = {text: state[newState[i].rows[0]], row: newOrigin[i].rows[0]};
            origin[newOrigin[i].rows[0]] = {text: origin[newOrigin[i].rows[0]], row: newState[i].rows[0]}
        }
    }
    for (let i = 0; i < state.length - 1; i++) {
        if (state[i].text != null && state[i + 1].text == null && state[i].row + 1 < origin.length && null == origin[state[i].row + 1].text &&
            state[i + 1] == origin[state[i].row + 1]) {
            state[i + 1] = {text: state[i + 1], row: state[i].row + 1};
            origin[state[i].row + 1] = {text: origin[state[i].row + 1], row: i + 1}
        }
    }
    for (let i = state.length - 1; i > 0; i--) {
        if (null != state[i].text && null == state[i - 1].text && state[i].row > 0 && null == origin[state[i].row - 1].text &&
            state[i - 1] == origin[state[i].row - 1]) {
            state[i - 1] = {text: state[i - 1], row: state[i].row - 1};
            origin[state[i].row - 1] = {text: origin[state[i].row - 1], row: i - 1}
        }
    }
    return {origin: origin, state: state}
}

function escape(string) {
    string = string.replace(/&/g, '&amp;');
    string = string.replace(/</g, '&lt;');
    string = string.replace(/>/g, '&gt;');
    string = string.replace(/"/g, '&quot;');
    return string;
}
