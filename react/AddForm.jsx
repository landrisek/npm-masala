import React, {Component} from 'react'
import ReactDOM from 'react-dom'
import EditForm from './EditForm.jsx'

export default class AddForm extends EditForm {
    constructor(props){
        super(props)
    }
}
ReactDOM.render(<EditForm />, document.getElementById('addForm'))